<?php

namespace App\RAG\Generators;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqAnswerGenerator implements AnswerGeneratorInterface
{
    public function generate(string $prompt): string
    {
        set_time_limit(150);

        $response = Http::withToken(config('rag.completion.groq.api_key'))
            ->timeout(120)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => config('rag.completion.groq.model'),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'El servicio de generación de Groq respondió con un error: '.$response->body()
            );
        }

        $answer = $response->json('choices.0.message.content');

        if (! is_string($answer)) {
            throw new RuntimeException('El servicio de generación de Groq devolvió una respuesta inesperada.');
        }

        return trim($answer);
    }
}
