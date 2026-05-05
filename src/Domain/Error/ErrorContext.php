<?php

declare(strict_types=1);

namespace SelfHealLM\Domain\Error;

final class ErrorContext
{
    /**
     * @param array<int, string> $stack
     */
    public function __construct(
        public readonly string $message,
        public readonly string $filePath,
        public readonly int $line,
        public readonly array $stack,
        public readonly string $snippet,
        public readonly string $rawLogLine
    ) {
    }
}
