<?php

declare(strict_types=1);

namespace SelfHealLM\Infrastructure\Reporting;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Http;
use SelfHealLM\Contracts\ReporterInterface;
use SelfHealLM\Domain\Reporting\HealingReport;

final class WebhookReporter implements ReporterInterface
{
    public function __construct(private readonly Repository $config)
    {
    }

    public function report(HealingReport $report): void
    {
        if (!$this->config->get('self-heal.webhook.enabled', false)) {
            return;
        }

        $url = (string) $this->config->get('self-heal.webhook.url', '');
        if ($url === '') {
            return;
        }

        $token = (string) $this->config->get('self-heal.webhook.token', '');
        $request = Http::acceptJson()->asJson();
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $request->post($url, $report->payload);
    }
}
