<?php

namespace App\RAG\Chunkers;

class FixedSizeTextChunker implements TextChunkerInterface
{
    public function chunk(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $chunkSize = (int) config('rag.chunk_size');
        $chunkOverlap = (int) config('rag.chunk_overlap');
        $length = mb_strlen($text);

        if ($length <= $chunkSize) {
            return [$text];
        }

        $chunks = [];
        $start = 0;

        while ($start < $length) {
            $end = min($start + $chunkSize, $length);

            if ($end < $length) {
                $end = $this->findBreakPoint($text, $start, $end);
            }

            $chunk = trim(mb_substr($text, $start, $end - $start));

            if ($chunk !== '') {
                $chunks[] = $chunk;
            }

            if ($end >= $length) {
                break;
            }

            $start = max($end - $chunkOverlap, $start + 1);
            $start = $this->snapToWordStart($text, $start, $length);
        }

        return $chunks;
    }

    private function findBreakPoint(string $text, int $start, int $end): int
    {
        $window = mb_substr($text, $start, $end - $start);
        $breakPosition = mb_strrpos($window, "\n");

        if ($breakPosition === false) {
            $breakPosition = mb_strrpos($window, ' ');
        }

        $windowLength = $end - $start;

        if ($breakPosition === false || $breakPosition < $windowLength * 0.5) {
            return $end;
        }

        return $start + $breakPosition;
    }

    private function snapToWordStart(string $text, int $position, int $length): int
    {
        if ($position <= 0 || $position >= $length) {
            return $position;
        }

        $previousChar = mb_substr($text, $position - 1, 1);

        if ($previousChar === ' ' || $previousChar === "\n") {
            return $position;
        }

        $remaining = mb_substr($text, $position);
        $nextSpace = mb_strpos($remaining, ' ');
        $nextNewline = mb_strpos($remaining, "\n");
        $offsets = array_filter([$nextSpace, $nextNewline], fn ($offset) => $offset !== false);

        if ($offsets === []) {
            return $position;
        }

        return $position + min($offsets) + 1;
    }
}
