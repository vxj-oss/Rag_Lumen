<?php

return [
    'models' => array_values(array_filter(array_map('trim', explode(',', env('GROQ_FREE_MODELS', 'openai/gpt-oss-120b,openai/gpt-oss-20b'))))),
    'allowed_free_models' => ['openai/gpt-oss-120b', 'openai/gpt-oss-20b', 'qwen/qwen3.8-27b'],
    'transcription_models' => ['whisper-large-v3-turbo', 'whisper-large-v3'],
    'daily_request_limit' => (int) env('ASSISTANT_DAILY_REQUEST_LIMIT', 200),
    'timeout' => 10,
    'max_completion_tokens' => 1800,
    'max_audio_kb' => 5120,
    'backup_enabled' => (bool) env('WORKSPACE_BACKUP_ENABLED', true),
];
