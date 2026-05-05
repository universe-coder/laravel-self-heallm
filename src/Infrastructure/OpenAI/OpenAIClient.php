<?php

declare(strict_types=1);

namespace SelfHealLM\Infrastructure\OpenAI;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Http;
use SelfHealLM\Contracts\LLMClientInterface;
use SelfHealLM\Domain\Error\ErrorContext;
use SelfHealLM\Domain\Fix\FixOperation;
use SelfHealLM\Domain\Fix\FixProposal;

final class OpenAIClient implements LLMClientInterface
{
    public function __construct(private readonly Repository $config)
    {
    }

    public function proposeFix(ErrorContext $errorContext): FixProposal
    {
        $baseUrl = rtrim((string) $this->config->get('self-heal.openai.base_url', ''), '/');
        $token = (string) $this->config->get('self-heal.openai.token', '');
        $model = (string) $this->config->get('self-heal.openai.model', 'gpt-4.1-mini');
        $timeout = (int) $this->config->get('self-heal.openai.timeout', 30);

        if ($baseUrl === '' || $token === '') {
            return new FixProposal('OpenAI not configured.', [], ['Missing OpenAI base_url or token.']);
        }

        $prompt = $this->buildPrompt($errorContext);

        $response = Http::timeout($timeout)
            ->withToken($token)
            ->post($baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a secure PHP/Laravel fixer. Return strict JSON only.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
            ]);

        if (!$response->successful()) {
            return new FixProposal('OpenAI request failed.', [], ['HTTP ' . $response->status()]);
        }

        $content = (string) data_get($response->json(), 'choices.0.message.content', '');
        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            return new FixProposal('Invalid model response.', [], ['Model returned non-JSON payload.']);
        }

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
PROMPT;
    }
}
