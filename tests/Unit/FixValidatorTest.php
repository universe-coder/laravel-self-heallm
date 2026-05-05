<?php

declare(strict_types=1);

namespace SelfHealLM\Tests\Unit;

use SelfHealLM\Domain\Fix\FixOperation;
use SelfHealLM\Domain\Fix\FixProposal;
use SelfHealLM\Domain\Fix\FixValidator;
use SelfHealLM\Tests\TestCase;

final class FixValidatorTest extends TestCase
{
    public function test_it_rejects_forbidden_path(): void
    {
        $proposal = new FixProposal('test', [
            new FixOperation('.env', 'APP_DEBUG=false', 'APP_DEBUG=true'),
        ]);

        $result = (new FixValidator())->validate($proposal, ['app/', 'config/'], ['.env'], 3);

        self::assertFalse($result->isValid);
        self::assertNotEmpty($result->errors);
    }

    public function test_it_accepts_safe_operation(): void
    {
        $proposal = new FixProposal('test', [
            new FixOperation('app/Services/Foo.php', 'return false;', 'return true;'),
        ]);

        $result = (new FixValidator())->validate($proposal, ['app/'], ['vendor/'], 3);

        self::assertTrue($result->isValid);
        self::assertCount(1, $result->safeOperations);
    }
}
