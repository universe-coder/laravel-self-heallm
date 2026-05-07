<?php

declare(strict_types=1);

namespace SelfHealLM\Infrastructure\LLM;

use SelfHealLM\Domain\Error\ErrorContext;
use SelfHealLM\Domain\Fix\FixOperation;
use SelfHealLM\Domain\Fix\FixProposal;

trait BuildsFixProposal
{
    private function buildPrompt(ErrorContext $context): string
    {
        return <<<PROMPT
Analyze Laravel error and propose minimal safe fixes.

Return JSON object:
{
  "summary": "short description",
  "warnings": ["optional warning"],
  "operations": [
    {
      "file_path": "app/Example.php",
      "find": "exact old string",
      "replace": "exact new string"
    }
  ]
}

Error message:
{$context->message}

File:
{$context->filePath}

Line:
{$context->line}

Code snippet:
{$context->snippet}

Full file content (truncated by config if needed):
{$context->fileContent}
PROMPT;
    }

    private function proposalFromJsonContent(string $content): FixProposal
    {
        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            return new FixProposal('Invalid model response.', [], ['Model returned non-JSON payload.']);
        }

        return $this->proposalFromArray($decoded);
    }

    /**
     * @param array<string, mixed> $decoded
     */
    private function proposalFromArray(array $decoded): FixProposal
    {
        $summary = (string) ($decoded['summary'] ?? 'Fix proposal');
        $warnings = array_values(array_map('strval', (array) ($decoded['warnings'] ?? [])));
        $operations = [];

        foreach ((array) ($decoded['operations'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $operations[] = new FixOperation(
                (string) ($item['file_path'] ?? ''),
                (string) ($item['find'] ?? ''),
                (string) ($item['replace'] ?? '')
            );
        }

        return new FixProposal($summary, $operations, $warnings);
    }
}
