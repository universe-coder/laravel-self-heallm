<?php

declare(strict_types=1);

namespace SelfHealLM\Domain\Fix;

final class FixOperation
{
    public function __construct(
        public readonly string $filePath,
        public readonly string $find,
        public readonly string $replace
    ) {
    }
}
