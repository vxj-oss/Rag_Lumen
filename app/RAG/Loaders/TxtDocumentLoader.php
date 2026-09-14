<?php

namespace App\RAG\Loaders;

class TxtDocumentLoader implements DocumentLoaderInterface
{
    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, [
            'text/plain',
            'text/markdown',
            'text/x-markdown',
            'text/csv',
            'application/csv',
        ], true);
    }

    public function extractText(string $filePath): string
    {
        return (string) file_get_contents($filePath);
    }
}
