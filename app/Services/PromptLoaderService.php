<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class PromptLoaderService{
    public static function load(string $path, array $vars = []): string
    {
        $fullPath = app_path("Prompts/{$path}.txt");

        if (! File::exists($fullPath)) {
            throw new RuntimeException("Prompt not found: {$path}");
        }

        $prompt = File::get($fullPath);

        foreach ($vars as $key => $value) {
            $prompt = str_replace('{{' . $key . '}}', $value, $prompt);
        }

        return $prompt;
    }
}
