<?php

declare(strict_types=1);

namespace SelfHealLM\Contracts;

use SelfHealLM\Domain\Reporting\HealingReport;

interface ReporterInterface
{
    public function report(HealingReport $report): void;
}
