<?php

return [
    'storage_disk' => 'local',

    'storage_path' => 'rag-documents',

    'allowed_mime_types' => [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'text/markdown',
        'text/csv',
    ],

    'max_upload_size_kb' => 10240,

    'chunk_size' => 800,

    'chunk_overlap' => 100,

    'retrieval_top_k' => 5,

    'context_max_chars' => 6000,

    
    'embedding' => [
        'provider' => env('RAG_EMBEDDING_PROVIDER', 'ollama'),
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_EMBEDDING_MODEL', 'all-minilm'),
        'dimensions' => 384,
        'cohere' => [
            'api_key' => env('COHERE_API_KEY'),
            'model' => env('COHERE_EMBEDDING_MODEL', 'embed-multilingual-v3.0'),
            'dimensions' => 1024,
        ],
    ],

    'completion' => [
        'provider' => env('RAG_COMPLETION_PROVIDER', 'ollama'),
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_COMPLETION_MODEL', 'llama3:8b'),
        'groq' => [
            'api_key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_COMPLETION_MODEL', 'openai/gpt-oss-120b'),
        ],
    ],
];
