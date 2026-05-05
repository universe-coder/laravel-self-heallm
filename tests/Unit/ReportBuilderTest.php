<?php

declare(strict_types=1);

namespace SelfHealLM\Tests\Unit;

use SelfHealLM\Application\ReportBuilder;
use SelfHealLM\Domain\Error\ErrorContext;
use SelfHealLM\Domain\Fix\FixProposal;
use SelfHealLM\Domain\Fix\ValidationResult;
use SelfHealLM\Tests\TestCase;

final class ReportBuilderTest extends TestCase
{
    public function test_it_builds_report_payload(): void
    {
        $context = new ErrorContext('boom', 'app/X.php', 12, [], '12: code', '<?php echo 1;', 'raw');
        $proposal = new FixProposal('fix', []);
        $validation = new ValidationResult(true, [], []);
        $result = ['applied' => 1, 'failed' => 0, 'details' => ['ok']];

        $report = (new ReportBuilder())->build($context, $proposal, $validation, $result, true, false);

        self::assertIsArray($report->payload);
        self::assertSame('boom', $report->payload['error']['message']);
        self::assertSame(1, $report->payload['execution']['applied']);
    }
}
