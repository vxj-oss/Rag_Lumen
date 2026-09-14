<?php

namespace App\RAG\Loaders;

interface DocumentLoaderInterface
{
    public function supports(string $mimeType): bool;

    public function extractText(string $filePath): string;
}
