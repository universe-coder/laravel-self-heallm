<?php

declare(strict_types=1);

namespace SelfHealLM\Application;

use SelfHealLM\Domain\Error\ErrorContext;
use SelfHealLM\Domain\Fix\FixProposal;
use SelfHealLM\Domain\Fix\ValidationResult;
use SelfHealLM\Domain\Reporting\HealingReport;

final class ReportBuilder
{
    /**
     * @param array{applied: int, failed: int, details: array<int, string>} $applyResult
     */
    public function build(
        ErrorContext $context,
        FixProposal $proposal,
        ValidationResult $validation,
        array $applyResult,
        bool $autoApply,
        bool $dryRun
    ): HealingReport {
        return new HealingReport([
            'timestamp' => now()->toIso8601String(),
            'error' => [
                'message' => $context->message,
                'file_path' => $context->filePath,
                'line' => $context->line,
                'snippet' => $context->snippet,
            ],
            'fix' => [
                'summary' => $proposal->summary,
                'warnings' => $proposal->warnings,
                'operations_count' => count($proposal->operations),
            ],
            'validation' => [
                'is_valid' => $validation->isValid,
                'errors' => $validation->errors,
                'safe_operations_count' => count($validation->safeOperations),
            ],
            'execution' => [
                'auto_apply' => $autoApply,
                'dry_run' => $dryRun,
                'applied' => $applyResult['applied'],
                'failed' => $applyResult['failed'],
                'details' => $applyResult['details'],
            ],
        ]);
    }
}
