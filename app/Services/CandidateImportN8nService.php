<?php

namespace App\Services;

use App\Models\Candidate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CandidateImportN8nService
{
    public function __construct(private readonly DriveCvService $driveCv)
    {
    }

    public function importViaN8n(UploadedFile $file): array
    {
        $recruiterId = 1;//change this once u can

        if (!$recruiterId) {
            return [
                'imported' => 0,
                'rows' => [],
                'errors' => ['Unauthenticated'],
            ];
        }

        $n8n = $this->fetchRowsFromN8n($file, $recruiterId);

        if (!$n8n['ok']) {
            return $n8n['response'];
        }

        $rows = $n8n['rows'];

        if (!is_array($rows) || count($rows) === 0) {
            return [
                'imported' => 0,
                'rows' => [],
                'errors' => ['No rows returned from n8n'],
            ];
        }

        $batch = $this->buildBatch($rows, $recruiterId);

        if (count($batch['insertRows']) === 0) {
            return [
                'imported' => 0,
                'rows' => [],
                'errors' => $batch['errors'],
            ];
        }

        $out = $this->insertAndAttachCvs(
            $batch['insertRows'],
            $batch['meta'],
            $recruiterId,
            $batch['useTimestamps'],
            $batch['batchTime']
        );

        return [
            'imported' => count(array_filter($out, fn($r) => $r['status'] === 'ok')),
            'rows' => $out,
            'errors' => $batch['errors'],
        ];
    }

    private function fetchRowsFromN8n(UploadedFile $file, int $recruiterId): array
    {
        $n8nUrl = config('services.n8n.excel_parse_webhook');

        $res = Http::timeout(180)
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post($n8nUrl, [
                'recruiter_id' => $recruiterId,
            ]);

        if (!$res->successful()) {
            return [
                'ok' => false,
                'response' => [
                    'imported' => 0,
                    'rows' => [],
                    'errors' => [
                        'n8n_failed' => [
                            'status' => $res->status(),
                            'body' => $res->body(),
                        ],
                    ],
                ],
            ];
        }

        $data = $res->json();
        $rows = $data['rows'] ?? $data;

        if (!is_array($rows)) {
            $rows = [];
        }

        return [
            'ok' => true,
            'rows' => $rows,
        ];
    }

    private function buildBatch(array $rows, int $recruiterId): array
    {
        $candidateModel = new Candidate();
        $useTimestamps = $candidateModel->usesTimestamps();
        $batchTime = now();

        $insertRows = [];
        $meta = [];
        $errors = [];

        collect($rows)->each(function ($row, $idx) use (
            $recruiterId,
            $useTimestamps,
            $batchTime,
            &$insertRows,
            &$meta,
            &$errors
        ) {
            $row = (array) $row;
            $fullName = $row['full_name'] ?? null;
            $email = isset($row['email']) ? strtolower(trim((string) $row['email'])) : null;

            if (!$fullName || !$email) {
                $errors[] = "Row " . ($idx + 1) . ": missing full_name/email";
                return;
            }

            $item = [
                'recruiter_id' => (int) $recruiterId,
                'full_name'    => $fullName,
                'email'        => $email,
                'phone_number' => $row['phone_number'] ?? null,
                'level'        => $row['level'] ?? null,
                'github_url'   => $row['github_url'] ?? null,
                'linkedin_url' => $row['linkedin_url'] ?? null,
                'cv_path'      => null,
                'age'          => (isset($row['age']) && is_numeric($row['age'])) ? (int) $row['age'] : null,
                'location'     => $row['location'] ?? null,
            ];

            if ($useTimestamps) {
                $item['created_at'] = $batchTime;
                $item['updated_at'] = $batchTime;
            }

            $insertRows[] = $item;

            $meta[] = [
                'row_number'   => $idx + 1,
                'email'        => $email,
                'cv_drive_url' => $row['cv_drive_url'] ?? null,
            ];
        });

        return [
            'insertRows' => $insertRows,
            'meta' => $meta,
            'errors' => $errors,
            'useTimestamps' => $useTimestamps,
            'batchTime' => $batchTime,
        ];
    }

    private function insertAndAttachCvs(array $insertRows, array $meta, int $recruiterId, bool $useTimestamps, $batchTime): array
    {
        $out = [];

        DB::beginTransaction();
        try {
            Candidate::insert($insertRows);

            $emails = array_values(array_unique(array_column($meta, 'email')));

            $query = Candidate::query()
                ->where('recruiter_id', $recruiterId)
                ->whereIn('email', $emails);

            if ($useTimestamps) {
                $query->where('created_at', $batchTime);
            }

            $inserted = $query->get(['id', 'email']);

            $emailToId = $inserted
                ->groupBy('email')
                ->map(fn($group) => $group->max('id'))
                ->all();

            collect($meta)->each(function ($m) use (&$out, $emailToId, $useTimestamps) {
                $email = $m['email'];
                $candidateId = $emailToId[$email] ?? null;

                if (!$candidateId) {
                    $out[] = [
                        'status' => 'failed',
                        'row'    => $m['row_number'],
                        'email'  => $email,
                        'error'  => 'Candidate inserted but ID not found for batch',
                    ];
                    return;
                }

                $cvPath = null;

                if (!empty($m['cv_drive_url'])) {
                    try {
                        $cvPath = $this->driveCv->storeFromDriveUrl($m['cv_drive_url'], $candidateId);

                        Candidate::whereKey($candidateId)->update(
                            $useTimestamps
                                ? ['cv_path' => $cvPath, 'updated_at' => now()]
                                : ['cv_path' => $cvPath]
                        );
                    } catch (\Throwable $e) {
                        $out[] = [
                            'status'       => 'cv_failed',
                            'row'          => $m['row_number'],
                            'candidate_id' => $candidateId,
                            'email'        => $email,
                            'error'        => $e->getMessage(),
                        ];
                        return;
                    }
                }

                $out[] = [
                    'status'       => 'ok',
                    'row'          => $m['row_number'],
                    'candidate_id' => $candidateId,
                    'email'        => $email,
                    'cv_path'      => $cvPath,
                ];
            });

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $out;
    }
}
