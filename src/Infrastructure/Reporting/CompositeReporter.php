<?php

declare(strict_types=1);

namespace SelfHealLM\Infrastructure\Reporting;

use SelfHealLM\Contracts\ReporterInterface;
use SelfHealLM\Domain\Reporting\HealingReport;

final class CompositeReporter implements ReporterInterface
{
    /**
     * @param array<int, ReporterInterface> $reporters
     */
    public function __construct(private readonly array $reporters)
    {
    }

    public function report(HealingReport $report): void
    {
        foreach ($this->reporters as $reporter) {
            $reporter->report($report);
        }
    }
}
