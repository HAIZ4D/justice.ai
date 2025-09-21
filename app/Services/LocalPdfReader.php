<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class LocalPdfReader
{
    /**
     * Extract text lines per page from a local PDF file (no coordinates).
     * Returns: [ [ 'page' => int, 'text' => string, 'bbox' => null ], ... ]
     */
    public function extract(string $localPath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($localPath);

        $lines = [];
        $pageNum = 0;

        foreach ($pdf->getPages() as $page) {
            $pageNum++;
            // Normalize line breaks; split into rough "lines"
            $text = preg_replace('/\r\n|\r|\n/', "\n", $page->getText());
            foreach (explode("\n", $text) as $row) {
                $row = trim($row);
                if ($row !== '') {
                    $lines[] = ['page' => $pageNum, 'text' => $row, 'bbox' => null];
                }
            }
        }
        return $lines;
    }
}
