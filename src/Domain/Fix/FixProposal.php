<?php

declare(strict_types=1);

namespace SelfHealLM\Domain\Fix;

final class FixProposal
{
    /**
     * @param array<int, FixOperation> $operations
     * @param array<int, string> $warnings
     */
    public function __construct(
        public readonly string $summary,
        public readonly array $operations,
        public readonly array $warnings = []
    ) {
    }
}
