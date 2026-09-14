<?php

namespace App\RAG\Loaders;

use Smalot\PdfParser\Parser;

class PdfDocumentLoader implements DocumentLoaderInterface
{
    public function supports(string $mimeType): bool
    {
        return $mimeType === 'application/pdf';
    }

    public function extractText(string $filePath): string
    {
        $parser = new Parser();
        $document = $parser->parseFile($filePath);

        return $document->getText();
    }
}
