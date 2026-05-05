<?php

declare(strict_types=1);

namespace SelfHealLM\Infrastructure\Reporting;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Http;
use SelfHealLM\Contracts\ReporterInterface;
use SelfHealLM\Domain\Reporting\HealingReport;

final class SlackReporter implements ReporterInterface
{
    public function __construct(private readonly Repository $config)
    {
    }

    public function report(HealingReport $report): void
    {
        if (!$this->config->get('self-heal.slack.enabled', false)) {
            return;
        }

        $webhookUrl = (string) $this->config->get('self-heal.slack.webhook_url', '');
        if ($webhookUrl === '') {
            return;
        }

        $payload = $report->payload;
        $text = sprintf(
            "*Self-heal report*\n*Validation:* %s\n*Error:* %s\n*File:* `%s:%s`\n*Applied:* %s\n*Failed:* %s",
            (bool) data_get($payload, 'validation.is_valid', false) ? 'valid' : 'invalid',
            (string) data_get($payload, 'error.message', 'unknown'),
            (string) data_get($payload, 'error.file_path', '-'),
            (string) data_get($payload, 'error.line', '-'),
            (string) data_get($payload, 'execution.applied', 0),
            (string) data_get($payload, 'execution.failed', 0)
        );

        Http::asJson()->post($webhookUrl, [
            'text' => mb_substr($text, 0, 3000),
        ]);
    }
}
