<?php

declare(strict_types=1);

namespace SelfHealLM\Domain\Fix;

final class ValidationResult
{
    /**
     * @param array<int, string> $errors
     * @param array<int, FixOperation> $safeOperations
     */
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors,
        public readonly array $safeOperations
    ) {
    }
}
