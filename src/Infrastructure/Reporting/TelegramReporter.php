<?php

declare(strict_types=1);

namespace SelfHealLM\Infrastructure\Reporting;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Http;
use SelfHealLM\Contracts\ReporterInterface;
use SelfHealLM\Domain\Reporting\HealingReport;

final class TelegramReporter implements ReporterInterface
{
    public function __construct(private readonly Repository $config)
    {
    }

    public function report(HealingReport $report): void
    {
        if (!$this->config->get('self-heal.telegram.enabled', true)) {
            return;
        }

        $botToken = (string) $this->config->get('self-heal.telegram.bot_token', '');
        $userId = (string) $this->config->get('self-heal.telegram.user_id', '');

        if ($botToken === '' || $userId === '') {
            return;
        }

        $payload = $report->payload;
        $text = sprintf(
            "Self-heal report\nStatus: %s\nError: %s\nFile: %s:%s\nApplied: %s, Failed: %s",
            (string) data_get($payload, 'validation.is_valid', false) ? 'valid' : 'invalid',
            (string) data_get($payload, 'error.message', 'unknown'),
            (string) data_get($payload, 'error.file_path', '-'),
            (string) data_get($payload, 'error.line', '-'),
            (string) data_get($payload, 'execution.applied', 0),
            (string) data_get($payload, 'execution.failed', 0),
        );

        Http::asForm()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $userId,
            'text' => mb_substr($text, 0, 4096),
        ]);
    }
}
