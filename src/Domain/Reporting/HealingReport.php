<?php

declare(strict_types=1);

namespace SelfHealLM\Domain\Reporting;

final class HealingReport
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly array $payload
    ) {
    }

    public function toJson(): string
    {
        return (string) json_encode($this->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
