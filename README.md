# laravel-self-heallm

Open-source Laravel package that detects recent application errors, asks an LLM for a fix proposal via OpenAI-compatible API, validates safety rules, applies fixes in hybrid mode, and sends healing reports.

## Installation

```bash
composer require self-heallm/laravel-self-heallm
php artisan vendor:publish --tag=self-heal-config
```

## Configuration

`config/self-heal.php` supports:

- OpenAI: `openai.base_url`, `openai.token`, `openai.model`, `openai.timeout`
- Telegram: `telegram.bot_token`, `telegram.user_id`, `telegram.enabled`
- Slack: `slack.enabled`, `slack.webhook_url`
- Generic webhook: `webhook.enabled`, `webhook.url`, `webhook.token`
- Sentry: `sentry.enabled`, `sentry.dsn`, `sentry.environment`
- Modes: `enabled`, `auto_apply`, `dry_run`
- Safety: `allowed_paths`, `forbidden_paths`, `max_files_per_fix`
- Deduplication: `deduplication.enabled`, `deduplication.ttl_seconds`, `deduplication.store_path`
- Model context size: `context.max_file_chars`
- Reporting: JSONL fallback path via `reporting.json_path`

Environment variables example:

```dotenv
SELF_HEAL_ENABLED=true
SELF_HEAL_AUTO_APPLY=false
SELF_HEAL_DRY_RUN=true
SELF_HEAL_OPENAI_BASE_URL=https://api.openai.com/v1
SELF_HEAL_OPENAI_TOKEN=sk-...
SELF_HEAL_OPENAI_MODEL=gpt-4.1-mini
SELF_HEAL_TELEGRAM_BOT_TOKEN=123:abc
SELF_HEAL_TELEGRAM_USER_ID=123456789
SELF_HEAL_SLACK_ENABLED=false
SELF_HEAL_SLACK_WEBHOOK_URL=
SELF_HEAL_WEBHOOK_ENABLED=false
SELF_HEAL_WEBHOOK_URL=
SELF_HEAL_WEBHOOK_TOKEN=
SELF_HEAL_SENTRY_ENABLED=false
SELF_HEAL_SENTRY_DSN=
SELF_HEAL_DEDUP_ENABLED=true
SELF_HEAL_DEDUP_TTL_SECONDS=600
SELF_HEAL_CONTEXT_MAX_FILE_CHARS=12000
```

## Run

```bash
php artisan self-heal:run
```

## Security Model

- Fixes are accepted only for whitelisted paths.
- Forbidden paths are rejected even if model suggests them.
- Empty replacement targets are rejected.
- `dry_run=false` with `auto_apply=true` applies validated patches automatically.
- Duplicate error fingerprint is skipped during deduplication TTL.

## Reporting

- Telegram notification is sent when configured.
- Slack incoming webhook notification is supported.
- Generic webhook POST notification is supported.
- Sentry envelope event reporting is supported.
- JSON report is always available as fallback when enabled.
