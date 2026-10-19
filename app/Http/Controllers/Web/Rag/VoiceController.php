<?php

namespace App\Http\Controllers\Web\Rag;

use App\Http\Controllers\Controller;
use App\Models\RagDocument;
use App\Services\Assistant\GroqGateway;
use Illuminate\Http\Request;

class VoiceController extends Controller
{
    public function __invoke(Request $request, GroqGateway $gateway)
    {
        if (! app()->runningInConsole()) set_time_limit(60);
        $this->authorize('viewAny', RagDocument::class);
        $request->validate(['audio' => ['required', 'file', 'mimetypes:audio/webm,video/webm,audio/ogg,application/ogg,audio/wav,audio/x-wav,audio/mpeg,audio/mp4,video/mp4', 'max:'.config('assistant.max_audio_kb')]]);
        try {
            return response()->json(['text' => $gateway->transcribe($request->file('audio'))]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }
    }
}
