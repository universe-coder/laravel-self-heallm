<?php

declare(strict_types=1);

namespace SelfHealLM\Console;

use Illuminate\Console\Command;
use SelfHealLM\Application\SelfHealPipeline;

final class RunSelfHealCommand extends Command
{
    protected $signature = 'self-heal:run';

    protected $description = 'Detect and heal latest Laravel error using LLM.';

    public function handle(SelfHealPipeline $pipeline): int
    {
        $result = $pipeline->run();
        $this->info('self-heal status: ' . ($result['status'] ?? 'unknown'));

        if (isset($result['reason'])) {
            $this->line('reason: ' . $result['reason']);
        }

        return self::SUCCESS;
    }
}
