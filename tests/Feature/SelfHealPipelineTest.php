<?php

declare(strict_types=1);

namespace SelfHealLM\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use SelfHealLM\Application\ErrorDeduplicator;
use SelfHealLM\Application\ErrorDetector;
use SelfHealLM\Application\PatchApplier;
use SelfHealLM\Application\ReportBuilder;
use SelfHealLM\Application\SelfHealPipeline;
use SelfHealLM\Contracts\LLMClientInterface;
use SelfHealLM\Contracts\ReporterInterface;
use SelfHealLM\Domain\Error\ErrorContext;
use SelfHealLM\Domain\Fix\FixOperation;
use SelfHealLM\Domain\Fix\FixProposal;
use SelfHealLM\Domain\Fix\FixValidator;
use SelfHealLM\Domain\Reporting\HealingReport;
use SelfHealLM\Tests\TestCase;

final class SelfHealPipelineTest extends TestCase
{
    public function test_it_runs_in_dry_run_mode_and_reports(): void
    {
        $logPath = storage_path('logs/self-heal-test.log');
        (new Filesystem())->ensureDirectoryExists(dirname($logPath));
        (new Filesystem())->put($logPath, '[2026-05-05] local.ERROR: RuntimeException in app/Services/DemoService.php on line 10');

        config()->set('self-heal.enabled', true);
        config()->set('self-heal.auto_apply', true);
        config()->set('self-heal.dry_run', true);
        config()->set('self-heal.log.path', $logPath);
        config()->set('self-heal.allowed_paths', ['app/']);
        config()->set('self-heal.forbidden_paths', ['vendor/']);
        config()->set('self-heal.deduplication.enabled', false);

        $state = (object) ['reported' => false];

        $pipeline = new SelfHealPipeline(
            config(),
            new ErrorDetector(new Filesystem()),
            new ErrorDeduplicator(new Filesystem()),
            new class implements LLMClientInterface {
                public function proposeFix(ErrorContext $errorContext): FixProposal
                {
                    return new FixProposal('dry run fix', [
                        new FixOperation('app/Services/DemoService.php', 'old', 'new'),
                    ]);
                }
            },
            new FixValidator(),
            new PatchApplier(new Filesystem()),
            new ReportBuilder(),
            new class($state) implements ReporterInterface {
                public function __construct(private object $state)
                {
                }

                public function report(HealingReport $report): void
                {
                    $this->state->reported = true;
                }
            }
        );

        $result = $pipeline->run();

        self::assertSame('ok', $result['status']);
        self::assertTrue($state->reported);
    }

    public function test_it_skips_duplicate_error_by_fingerprint(): void
    {
        $filesystem = new Filesystem();
        $logPath = storage_path('logs/self-heal-dedup.log');
        $dedupPath = storage_path('framework/cache/self-heal-dedup-test.json');
        $filesystem->ensureDirectoryExists(dirname($logPath));
        $filesystem->put($logPath, '[2026-05-05] local.ERROR: RuntimeException in app/Services/DemoService.php on line 10');

        config()->set('self-heal.enabled', true);
        config()->set('self-heal.auto_apply', false);
        config()->set('self-heal.dry_run', true);
        config()->set('self-heal.log.path', $logPath);
        config()->set('self-heal.allowed_paths', ['app/']);
        config()->set('self-heal.forbidden_paths', ['vendor/']);
        config()->set('self-heal.deduplication.enabled', true);
        config()->set('self-heal.deduplication.ttl_seconds', 3600);
        config()->set('self-heal.deduplication.store_path', $dedupPath);
        $filesystem->put($dedupPath, '{}');

        $llmCalls = (object) ['count' => 0];

        $pipeline = new SelfHealPipeline(
            config(),
            new ErrorDetector($filesystem),
            new ErrorDeduplicator($filesystem),
            new class($llmCalls) implements LLMClientInterface {
                public function __construct(private object $llmCalls)
                {
                }

                public function proposeFix(ErrorContext $errorContext): FixProposal
                {
                    $this->llmCalls->count++;
                    return new FixProposal('dry run fix', [
                        new FixOperation('app/Services/DemoService.php', 'old', 'new'),
                    ]);
                }
            },
            new FixValidator(),
            new PatchApplier($filesystem),
            new ReportBuilder(),
            new class implements ReporterInterface {
                public function report(HealingReport $report): void
                {
                }
            }
        );

        $first = $pipeline->run();
        $second = $pipeline->run();

        self::assertSame('ok', $first['status']);
        self::assertSame('skipped', $second['status']);
        self::assertSame('duplicate error fingerprint', $second['reason']);
        self::assertSame(1, $llmCalls->count);
    }
}
