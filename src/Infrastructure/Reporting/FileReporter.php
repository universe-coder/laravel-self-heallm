<?php

declare(strict_types=1);

namespace SelfHealLM\Infrastructure\Reporting;

use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use SelfHealLM\Contracts\ReporterInterface;
use SelfHealLM\Domain\Reporting\HealingReport;

final class FileReporter implements ReporterInterface
{
    public function __construct(
        private readonly Repository $config,
        private readonly Filesystem $files
    ) {
    }

    public function report(HealingReport $report): void
    {
        if (!$this->config->get('self-heal.reporting.store_json', true)) {
            return;
        }

        $path = (string) $this->config->get('self-heal.reporting.json_path');
        $directory = dirname($path);
        if (!$this->files->exists($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->append($path, $report->toJson() . PHP_EOL);
    }
}
