<?php

declare(strict_types=1);

namespace SelfHealLM\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use SelfHealLM\Application\ErrorDeduplicator;
use SelfHealLM\Domain\Error\ErrorContext;
use SelfHealLM\Tests\TestCase;

final class ErrorDeduplicatorTest extends TestCase
{
    public function test_it_blocks_same_fingerprint_inside_ttl(): void
    {
        $filesystem = new Filesystem();
        $storePath = storage_path('framework/cache/self-heal-dedup-unit.json');
        $filesystem->ensureDirectoryExists(dirname($storePath));
        $filesystem->put($storePath, '{}');

        $deduplicator = new ErrorDeduplicator($filesystem);
        $context = new ErrorContext('boom', 'app/X.php', 10, [], '', 'raw');
        $fingerprint = $deduplicator->fingerprint($context);

        self::assertTrue($deduplicator->shouldProcess($storePath, $fingerprint, 600));
        self::assertFalse($deduplicator->shouldProcess($storePath, $fingerprint, 600));
    }
}
