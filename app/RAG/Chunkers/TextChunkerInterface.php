<?php

namespace App\RAG\Chunkers;

interface TextChunkerInterface
{
    
    public function chunk(string $text): array;
}
