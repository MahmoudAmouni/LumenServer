<?php

namespace App\Services;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DriveCvService
{
    public function storeFromDriveUrl(string $driveUrl, int $candidateId): string
    {
        $fileId = $this->extractDriveFileId($driveUrl);
        if (!$fileId) {
            throw new \InvalidArgumentException("Invalid Google Drive URL");
        }

        $downloadUrl = "https://drive.google.com/uc?export=download&id={$fileId}";

        $filename = "candidate_{$candidateId}_" . Str::random(10) . ".pdf";
        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir))
            mkdir($tmpDir, 0755, true);

        $tmpPath = $tmpDir . DIRECTORY_SEPARATOR . $filename;

        $res = Http::withOptions(['allow_redirects' => true])
            ->timeout(90)
            ->sink($tmpPath)
            ->get($downloadUrl);

        if (!$res->successful()) {
            @unlink($tmpPath);
            throw new \RuntimeException("Drive download failed: HTTP " . $res->status());
        }

        $head = @file_get_contents($tmpPath, false, null, 0, 4);
        if ($head !== '%PDF') {
            @unlink($tmpPath);
            throw new \RuntimeException("Not a PDF (Drive link private or blocked).");
        }

        $storedPath = Storage::disk('public')->putFileAs('cvs', new File($tmpPath), $filename);
        @unlink($tmpPath);

        return $storedPath; 
    }

    private function extractDriveFileId(string $url): ?string
    {
        if (preg_match('~/file/d/([^/]+)~', $url, $m))
            return $m[1];
        if (preg_match('~[?&]id=([^&]+)~', $url, $m))
            return $m[1];
        return null;
    }
}
