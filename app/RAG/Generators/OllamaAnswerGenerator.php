<?php

namespace App\RAG\Generators;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaAnswerGenerator implements AnswerGeneratorInterface
{
    public function generate(string $prompt): string
    {
        set_time_limit(150);

        $response = Http::baseUrl(config('rag.completion.base_url'))
            ->timeout(120)
            ->post('/api/generate', [
                'model' => config('rag.completion.model'),
                'prompt' => $prompt,
                'stream' => false,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'El servicio de generación de Ollama respondió con un error: '.$response->body()
            );
        }

        $answer = $response->json('response');

        if (! is_string($answer)) {
            throw new RuntimeException('El servicio de generación de Ollama devolvió una respuesta inesperada.');
        }

        return trim($answer);
    }
}
