<?php

declare(strict_types=1);

namespace SelfHealLM\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SelfHealLM\LaravelSelfHealServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LaravelSelfHealServiceProvider::class];
    }
}
