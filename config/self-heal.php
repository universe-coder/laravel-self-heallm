<?php

declare(strict_types=1);

return [
    'enabled' => env('SELF_HEAL_ENABLED', true),
    'auto_apply' => env('SELF_HEAL_AUTO_APPLY', false),
    'dry_run' => env('SELF_HEAL_DRY_RUN', true),
    'llm' => [
        'provider' => env('SELF_HEAL_LLM_PROVIDER', 'openai'),
    ],
    'max_files_per_fix' => (int) env('SELF_HEAL_MAX_FILES_PER_FIX', 3),
    'allowed_paths' => [
        'app/',
        'routes/',
        'config/',
    ],
    'forbidden_paths' => [
        '.env',
        'vendor/',
        'storage/',
    ],
    'openai' => [
        'base_url' => env('SELF_HEAL_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'token' => env('SELF_HEAL_OPENAI_TOKEN'),
        'model' => env('SELF_HEAL_OPENAI_MODEL', 'gpt-4.1-mini'),
        'timeout' => (int) env('SELF_HEAL_OPENAI_TIMEOUT', 30),
    ],
    'anthropic' => [
        'base_url' => env('SELF_HEAL_ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
        'token' => env('SELF_HEAL_ANTHROPIC_TOKEN'),
        'model' => env('SELF_HEAL_ANTHROPIC_MODEL', 'claude-3-5-sonnet-latest'),
        'timeout' => (int) env('SELF_HEAL_ANTHROPIC_TIMEOUT', 30),
        'version' => env('SELF_HEAL_ANTHROPIC_VERSION', '2023-06-01'),
    ],
    'huggingface' => [
        'base_url' => env('SELF_HEAL_HUGGINGFACE_BASE_URL', 'https://router.huggingface.co/v1'),
        'token' => env('SELF_HEAL_HUGGINGFACE_TOKEN'),
        'model' => env('SELF_HEAL_HUGGINGFACE_MODEL', 'openai/gpt-oss-120b'),
        'timeout' => (int) env('SELF_HEAL_HUGGINGFACE_TIMEOUT', 30),
    ],
    'ollama' => [
        'base_url' => env('SELF_HEAL_OLLAMA_BASE_URL', 'http://localhost:11434/v1'),
        'token' => env('SELF_HEAL_OLLAMA_TOKEN'),
        'model' => env('SELF_HEAL_OLLAMA_MODEL', 'llama3.1'),
        'timeout' => (int) env('SELF_HEAL_OLLAMA_TIMEOUT', 30),
    ],
    'telegram' => [
        'enabled' => env('SELF_HEAL_TELEGRAM_ENABLED', true),
        'bot_token' => env('SELF_HEAL_TELEGRAM_BOT_TOKEN'),
        'user_id' => env('SELF_HEAL_TELEGRAM_USER_ID'),
    ],
    'slack' => [
        'enabled' => env('SELF_HEAL_SLACK_ENABLED', false),
        'webhook_url' => env('SELF_HEAL_SLACK_WEBHOOK_URL'),
    ],
    'webhook' => [
        'enabled' => env('SELF_HEAL_WEBHOOK_ENABLED', false),
        'url' => env('SELF_HEAL_WEBHOOK_URL'),
        'token' => env('SELF_HEAL_WEBHOOK_TOKEN'),
    ],
    'sentry' => [
        'enabled' => env('SELF_HEAL_SENTRY_ENABLED', false),
        'dsn' => env('SELF_HEAL_SENTRY_DSN'),
        'environment' => env('SELF_HEAL_SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),
    ],
    'reporting' => [
        'store_json' => env('SELF_HEAL_STORE_JSON_REPORT', true),
        'json_path' => env('SELF_HEAL_REPORT_PATH', storage_path('logs/self-heal-report.jsonl')),
    ],
    'deduplication' => [
        'enabled' => env('SELF_HEAL_DEDUP_ENABLED', true),
        'ttl_seconds' => (int) env('SELF_HEAL_DEDUP_TTL_SECONDS', 600),
        'store_path' => env('SELF_HEAL_DEDUP_STORE_PATH', storage_path('framework/cache/self-heal-dedup.json')),
    ],
    'log' => [
        'path' => env('SELF_HEAL_LOG_PATH', storage_path('logs/laravel.log')),
        'max_lines' => (int) env('SELF_HEAL_LOG_MAX_LINES', 200),
    ],
    'context' => [
        'max_file_chars' => (int) env('SELF_HEAL_CONTEXT_MAX_FILE_CHARS', 12000),
    ],
];
