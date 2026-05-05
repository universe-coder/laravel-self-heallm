<?php

declare(strict_types=1);

namespace SelfHealLM\Application;

use Illuminate\Config\Repository;
use SelfHealLM\Contracts\LLMClientInterface;
use SelfHealLM\Contracts\ReporterInterface;
use SelfHealLM\Domain\Fix\FixValidator;

final class SelfHealPipeline
{
    public function __construct(
        private readonly Repository $config,
        private readonly ErrorDetector $detector,
        private readonly ErrorDeduplicator $deduplicator,
        private readonly LLMClientInterface $client,
        private readonly FixValidator $validator,
        private readonly PatchApplier $applier,
        private readonly ReportBuilder $reportBuilder,
        private readonly ReporterInterface $reporter
    ) {
    }

    /**
     * @return array{status: string, reason?: string}
     */
    public function run(): array
    {
        if (!$this->config->get('self-heal.enabled', false)) {
            return ['status' => 'skipped', 'reason' => 'self-heal disabled'];
        }

        $context = $this->detector->detectFromLog(
            (string) $this->config->get('self-heal.log.path'),
            (int) $this->config->get('self-heal.log.max_lines', 200)
        );

        if ($context === null) {
            return ['status' => 'skipped', 'reason' => 'no error detected'];
        }

        if ((bool) $this->config->get('self-heal.deduplication.enabled', true)) {
            $fingerprint = $this->deduplicator->fingerprint($context);
            $canProcess = $this->deduplicator->shouldProcess(
                (string) $this->config->get('self-heal.deduplication.store_path'),
                $fingerprint,
                (int) $this->config->get('self-heal.deduplication.ttl_seconds', 600)
            );

            if (!$canProcess) {
                return ['status' => 'skipped', 'reason' => 'duplicate error fingerprint'];
            }
        }

        $proposal = $this->client->proposeFix($context);
        $validation = $this->validator->validate(
            $proposal,
            (array) $this->config->get('self-heal.allowed_paths', []),
            (array) $this->config->get('self-heal.forbidden_paths', []),
            (int) $this->config->get('self-heal.max_files_per_fix', 3)
        );

        $autoApply = (bool) $this->config->get('self-heal.auto_apply', false);
        $dryRun = (bool) $this->config->get('self-heal.dry_run', true);
        $applyResult = ['applied' => 0, 'failed' => 0, 'details' => []];

        if ($validation->isValid && $autoApply && !$dryRun) {
            $applyResult = $this->applier->apply($validation->safeOperations);
        }

        $report = $this->reportBuilder->build($context, $proposal, $validation, $applyResult, $autoApply, $dryRun);
        $this->reporter->report($report);

        return ['status' => 'ok'];
    }
}
