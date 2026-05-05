<?php

declare(strict_types=1);

namespace SelfHealLM\Domain\Fix;

final class FixValidator
{
    /**
     * @param array<int, string> $allowedPaths
     * @param array<int, string> $forbiddenPaths
     */
    public function validate(FixProposal $proposal, array $allowedPaths, array $forbiddenPaths, int $maxFiles): ValidationResult
    {
        $errors = [];
        $safeOperations = [];

        if (count($proposal->operations) === 0) {
            $errors[] = 'Model returned no operations.';
        }

        if (count($proposal->operations) > $maxFiles) {
            $errors[] = 'Operation count exceeds configured max_files_per_fix.';
        }

        foreach ($proposal->operations as $operation) {
            $isAllowed = $this->startsWithAny($operation->filePath, $allowedPaths);
            $isForbidden = $this->startsWithAny($operation->filePath, $forbiddenPaths);

            if ($operation->filePath === '' || !$isAllowed || $isForbidden) {
                $errors[] = sprintf('Unsafe path rejected: %s', $operation->filePath);
                continue;
            }

            if ($operation->find === '') {
                $errors[] = sprintf('Empty find pattern for %s', $operation->filePath);
                continue;
            }

            $safeOperations[] = $operation;
        }

        return new ValidationResult($errors === [], $errors, $safeOperations);
    }

    /**
     * @param array<int, string> $prefixes
     */
    private function startsWithAny(string $value, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
