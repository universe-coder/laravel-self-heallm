<?php

declare(strict_types=1);

namespace SelfHealLM\Infrastructure\Reporting;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Http;
use SelfHealLM\Contracts\ReporterInterface;
use SelfHealLM\Domain\Reporting\HealingReport;

final class SentryReporter implements ReporterInterface
{
    public function __construct(private readonly Repository $config)
    {
    }

    public function report(HealingReport $report): void
    {
        if (!$this->config->get('self-heal.sentry.enabled', false)) {
            return;
        }

        $dsn = (string) $this->config->get('self-heal.sentry.dsn', '');
        if ($dsn === '') {
            return;
        }

        $envelopeUrl = $this->toEnvelopeUrl($dsn);
        if ($envelopeUrl === null) {
            return;
        }

        $eventId = bin2hex(random_bytes(16));
        $payload = $report->payload;
        $timestamp = time();
        $environment = (string) $this->config->get('self-heal.sentry.environment', 'production');

        $event = [
            'event_id' => $eventId,
            'timestamp' => $timestamp,
            'level' => 'error',
            'environment' => $environment,
            'platform' => 'php',
            'message' => (string) data_get($payload, 'error.message', 'Self-heal error'),
            'extra' => [
                'self_heal_report' => $payload,
            ],
        ];

        $envelopeHeaders = ['event_id' => $eventId, 'dsn' => $dsn];
        $itemHeaders = ['type' => 'event'];
        $body = json_encode($envelopeHeaders) . "\n"
            . json_encode($itemHeaders) . "\n"
            . json_encode($event);

        Http::withHeaders(['Content-Type' => 'application/x-sentry-envelope'])
            ->withBody($body, 'application/x-sentry-envelope')
            ->post($envelopeUrl);
    }

    private function toEnvelopeUrl(string $dsn): ?string
    {
        $parts = parse_url($dsn);
        if (!is_array($parts) || !isset($parts['host'], $parts['path'])) {
            return null;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = trim($parts['path'], '/');

        $segments = $path === '' ? [] : explode('/', $path);
        $projectId = array_pop($segments);
        if ($projectId === null || $projectId === '') {
            return null;
        }
        $basePath = implode('/', $segments);
        $prefix = $basePath === '' ? '' : '/' . $basePath;

        return sprintf('%s://%s%s%s/api/%s/envelope/', $scheme, $host, $port, $prefix, $projectId);
    }
}
