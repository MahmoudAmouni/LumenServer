<?php

namespace App\Services\CV;

class CVSectionSplitter{
     protected array $sectionMap = [
        'summary' => ['summary', 'profile', 'about'],
        'skills' => ['skills', 'technologies', 'technical skills'],
        'experience' => ['experience', 'work experience', 'employment'],
        'projects' => ['projects', 'personal projects'],
        'education' => ['education'],
        'certifications' => ['certifications', 'certificates'],
    ];

    public function split(string $text): array{
        $lines = preg_split("/\r\n|\n|\r/", $text);
        $sections = [];
        $currentSection = 'general';

        foreach ($lines as $line) {
            $normalized = strtolower(trim($line));

            if ($section = $this->detectSection($normalized)) {
                $currentSection = $section;
                continue;
            }

            $sections[$currentSection][] = $line;
        }

        // Convert arrays to text blocks
        return collect($sections)
            ->map(fn ($lines) => trim(implode("\n", $lines)))
            ->filter()
            ->toArray();
    }

    protected function detectSection(string $line): ?string{
        foreach ($this->sectionMap as $section => $keywords) {
            foreach ($keywords as $keyword) {
                if ($line === $keyword || str_starts_with($line, $keyword)) {
                    return $section;
                }
            }
        }

        return null;
    }

}
