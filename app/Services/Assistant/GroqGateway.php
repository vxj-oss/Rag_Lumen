<?php

namespace App\Services\Assistant;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class GroqGateway
{
    public function enabled(): bool
    {
        return filled(config('rag.completion.groq.api_key'));
    }

    private function guardBudget(): void
    {
        if (! config('rag.completion.groq.api_key')) {
            throw new RuntimeException('El servicio de IA no está configurado.');
        }
        $key = 'assistant-budget-'.now()->toDateString();
        if (RateLimiter::tooManyAttempts($key, max(1, config('assistant.daily_request_limit')))) {
            throw new RuntimeException('Se alcanzó el presupuesto diario local de solicitudes gratuitas.');
        }
        RateLimiter::hit($key, (int) now()->endOfDay()->diffInSeconds(now(), true) + 1);
    }

    public function complete(array $messages, bool $json = false): string
    {
        $models = array_slice(array_values(array_intersect(config('assistant.models'), config('assistant.allowed_free_models'))), 0, 3);
        foreach ($models as $model) {
            $key = 'assistant-cooldown-'.md5($model);
            if (Cache::has($key)) {
                continue;
            }
            $this->guardBudget();
            try {
                $payload = ['model' => $model, 'messages' => $messages, 'temperature' => 0.1, 'max_completion_tokens' => config('assistant.max_completion_tokens')];
                if ($json) {
                    $payload['response_format'] = ['type' => 'json_object'];
                }
                $response = Http::withToken(config('rag.completion.groq.api_key'))->connectTimeout(5)->timeout(config('assistant.timeout'))->post('https://api.groq.com/openai/v1/chat/completions', $payload);
                Cache::put('assistant-model-status-'.md5($model), ['status' => $response->status(), 'at' => now()->toIso8601String(), 'remaining_requests' => $response->header('x-ratelimit-remaining-requests'), 'remaining_tokens' => $response->header('x-ratelimit-remaining-tokens')], now()->addDay());
                if (in_array($response->status(), [401, 403])) {
                    throw new RuntimeException('La clave o los permisos del servicio de IA requieren revisión.');
                }
                if ($response->successful() && is_string($text = $response->json('choices.0.message.content')) && trim($text) !== '') {
                    return trim($text);
                }
                $wait = min(3600, max(15, (int) $response->header('retry-after', '30')));
                Cache::put($key, true, $wait);
                if (! in_array($response->status(), [429, 500, 502, 503, 504, 404])) {
                    break;
                }
            } catch (ConnectionException $e) {
                Cache::put($key, true, 30);
            }
        }
        throw new RuntimeException('Los modelos gratuitos están temporalmente indisponibles. Puedes reintentar más tarde.');
    }

    public function transcribe(UploadedFile $file): string
    {
        foreach (config('assistant.transcription_models') as $model) {
            if (Cache::has('assistant-cooldown-'.md5($model))) {
                continue;
            }
            $this->guardBudget();
            try {
                $response = Http::withToken(config('rag.completion.groq.api_key'))->connectTimeout(5)->timeout(25)
                    ->attach('file', file_get_contents($file->getRealPath()), 'recording.'.$file->guessExtension())
                    ->post('https://api.groq.com/openai/v1/audio/transcriptions', ['model' => $model, 'language' => 'es', 'response_format' => 'json']);
                if ($response->successful() && is_string($text = $response->json('text'))) {
                    return trim($text);
                }
                if (in_array($response->status(), [401, 403, 400, 413])) {
                    break;
                }
                Cache::put('assistant-cooldown-'.md5($model), true, min(3600, max(15, (int) $response->header('retry-after', '30'))));
            } catch (ConnectionException $e) {
                Cache::put('assistant-cooldown-'.md5($model), true, 30);
            }
        }
        throw new RuntimeException('No se pudo transcribir con los modelos gratuitos. Puedes escribir tu pregunta.');
    }
}
