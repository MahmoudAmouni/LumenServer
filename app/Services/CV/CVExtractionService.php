<?php

namespace App\Services\CV;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;


class CVExtractionService{

    public function extract($url){
        $isTemp = false;
        $localPath = storage_path('app/public/' . ltrim($url, '/'));
        Log::debug("Attempting to extract CV from URL: $url, local path: $localPath");

        if (file_exists($localPath)) {
            $filePath = $localPath;
        } else {
            throw new Exception("File not found at path: $localPath");
        }

        if (!$filePath) {
            return null;
        }

        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $text = null;

        if ($ext === "pdf") {
            $text = $this->extractPdf($filePath);
        }

        if (in_array($ext, ["docx", "doc"])) {
            $text = $this->extractDocx($filePath);
        }

        if ($ext === "txt") {
            $text = file_get_contents($filePath);
        }

        if ($isTemp) {
            @unlink($filePath);
        }

        Log::debug("Extracted text length: " . strlen($text));
        return $text;
    }

    private function extractPdf($filePath){
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);

        return $pdf->getText();
    }


    private function extractDocx($filePath){
        $phpWord = IOFactory::load($filePath);
        $text = "";

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {

                if ($element instanceof Text) {
                    $text .= $element->getText() . "\n";
                }

                if ($element instanceof TextRun) {
                    foreach ($element->getElements() as $runElement) {
                        if ($runElement instanceof Text) {
                            $text .= $runElement->getText() . "\n";
                        }
                    }
                }
            }
        }

        return $text;
    }
}
