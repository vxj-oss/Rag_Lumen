<?php

namespace App\RAG\Generators;

interface AnswerGeneratorInterface
{
    public function generate(string $prompt): string;
}
