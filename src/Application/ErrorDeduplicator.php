<?php

declare(strict_types=1);

namespace SelfHealLM\Application;

use Illuminate\Filesystem\Filesystem;
use SelfHealLM\Domain\Error\ErrorContext;

final class ErrorDeduplicator
{
    public function __construct(private readonly Filesystem $files)
    {
    }

    public function fingerprint(ErrorContext $context): string
    {
        return hash('sha256', implode('|', [
            $context->message,
            $context->filePath,
            (string) $context->line,
            $context->rawLogLine,
        ]));
    }

    public function shouldProcess(string $storePath, string $fingerprint, int $ttlSeconds): bool
    {
        $state = $this->readState($storePath);
        $now = time();

        $lastSeen = isset($state[$fingerprint]) ? (int) $state[$fingerprint] : 0;
        if ($lastSeen > 0 && ($now - $lastSeen) < $ttlSeconds) {
            return false;
        }

        $state[$fingerprint] = $now;
        $this->persistState($storePath, $state);

        return true;
    }

    /**
     * @return array<string, int>
     */
    private function readState(string $storePath): array
    {
        if (!$this->files->exists($storePath)) {
            return [];
        }

        $decoded = json_decode((string) $this->files->get($storePath), true);
        if (!is_array($decoded)) {
            return [];
        }

        $state = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key) || !is_int($value)) {
                continue;
            }
            $state[$key] = $value;
        }

        return $state;
    }

    /**
     * @param array<string, int> $state
     */
    private function persistState(string $storePath, array $state): void
    {
        $directory = dirname($storePath);
        if (!$this->files->exists($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($storePath, (string) json_encode($state, JSON_PRETTY_PRINT));
    }
}
