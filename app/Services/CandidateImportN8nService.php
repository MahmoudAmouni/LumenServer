<?php

namespace App\Services;

use App\Jobs\IngestCandidateCv;
use App\Models\Candidate;
use App\Models\CandidateJob;
use App\Services\Candidate\CandidateIngestionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CandidateImportN8nService
{
    public function __construct(private readonly DriveCvService $driveCv)
    {
    }

    public function importViaN8n(UploadedFile $file , int $recruiterId , int $job_id): array
    {
        $recruiterId = $recruiterId;

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
            $job_id,
            $batch['useTimestamps'],
            $batch['batchTime']
        );

        return [
            'imported' => count(array_filter($out, fn($r) => $r['status'] === 'ok')),
            'rows' => $out,
            'errors' => $batch['errors'],
        ];
    }

    private function fetchRowsFromN8n(UploadedFile $file, int $recruiterId): array{
        $n8nUrl = config('services.n8n.excel_parse_webhook') ?? "http://localhost:5678/webhook-test/test";

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

    private function buildBatch(array $rows, int $recruiterId): array{
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

    private function insertAndAttachCvs(
        array $insertRows,
        array $meta,
        int $recruiterId,
        int $job_id,
        bool $useTimestamps,
        $batchTime
    ): array {

        $out = [];
        $candidateIds = [];

        DB::beginTransaction();

        try {

            // add to candidates table
            foreach (array_chunk($insertRows, 500) as $batch) {

                foreach ($batch as $row) {
                    $candidate = Candidate::create($row);
                    $candidateIds[$row['email']] = $candidate->id;
                }
            }
            
            // add to candidate_jobs table
            foreach (array_chunk($insertRows, 500) as $batch) {

                foreach ($batch as $row) {
                    $candidate = CandidateJob::create([
                        "candidate_id" => $candidateIds[$row['email']],
                        "job_id" => $job_id,
                        "source" => "linkedin",
                        "recruiter_id" => $recruiterId,
                    ]);
                }
            }

            collect($meta)->each(function ($m) use (
                &$out,
                &$candidateIds,
                $useTimestamps
            ) {

                $email = $m['email'];
                $candidateId = $candidateIds[$email] ?? null;

                if (!$candidateId) {
                    $out[] = [
                        'status' => 'failed',
                        'row'    => $m['row_number'],
                        'email'  => $email,
                        'error'  => 'Candidate inserted but ID not found',
                    ];
                    return;
                }

                $cvPath = null;

                if (!empty($m['cv_drive_url'])) {
                    try {

                        $cvPath = $this->driveCv
                            ->storeFromDriveUrl($m['cv_drive_url'], $candidateId);

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

            DB::afterCommit(function () use ($candidateIds){// dispatching jobs after commit to avoid issues with transactions
                foreach ($candidateIds as $id){
                    Log::debug("$id");
                    IngestCandidateCv::dispatch($id);
                }
            });

        } catch (\Throwable $e) {

            DB::rollBack();
            throw $e;

        }

        return $out;
    }

}
