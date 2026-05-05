<?php

declare(strict_types=1);

namespace SelfHealLM\Application;

use Illuminate\Filesystem\Filesystem;
use SelfHealLM\Domain\Fix\FixOperation;

final class PatchApplier
{
    public function __construct(private readonly Filesystem $files)
    {
    }

    /**
     * @param array<int, FixOperation> $operations
     * @return array{applied: int, failed: int, details: array<int, string>}
     */
    public function apply(array $operations): array
    {
        $applied = 0;
        $failed = 0;
        $details = [];

        foreach ($operations as $operation) {
            if (!$this->files->exists($operation->filePath)) {
                $failed++;
                $details[] = sprintf('File not found: %s', $operation->filePath);
                continue;
            }

            $current = (string) $this->files->get($operation->filePath);
            if (!str_contains($current, $operation->find)) {
                $failed++;
                $details[] = sprintf('Pattern not found in: %s', $operation->filePath);
                continue;
            }

            $updated = str_replace($operation->find, $operation->replace, $current);
            $this->files->put($operation->filePath, $updated);
            $applied++;
            $details[] = sprintf('Applied fix to: %s', $operation->filePath);
        }

        return [
            'applied' => $applied,
            'failed' => $failed,
            'details' => $details,
        ];
    }
}
