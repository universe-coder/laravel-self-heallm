<?php

declare(strict_types=1);

namespace SelfHealLM\Contracts;

use SelfHealLM\Domain\Error\ErrorContext;
use SelfHealLM\Domain\Fix\FixProposal;

interface LLMClientInterface
{
    public function proposeFix(ErrorContext $errorContext): FixProposal;
}
