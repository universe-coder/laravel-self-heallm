<?php

declare(strict_types=1);

namespace SelfHealLM\Infrastructure\Anthropic;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Http;
use SelfHealLM\Contracts\LLMClientInterface;
use SelfHealLM\Domain\Error\ErrorContext;
use SelfHealLM\Domain\Fix\FixProposal;
use SelfHealLM\Infrastructure\LLM\BuildsFixProposal;

final class AnthropicClient implements LLMClientInterface
{
    use BuildsFixProposal;

    public function __construct(private readonly Repository $config)
    {
    }

    public function proposeFix(ErrorContext $errorContext): FixProposal
    {
        $baseUrl = rtrim((string) $this->config->get('self-heal.anthropic.base_url', ''), '/');
        $token = (string) $this->config->get('self-heal.anthropic.token', '');
        $model = (string) $this->config->get('self-heal.anthropic.model', 'claude-3-5-sonnet-latest');
        $timeout = (int) $this->config->get('self-heal.anthropic.timeout', 30);
        $version = (string) $this->config->get('self-heal.anthropic.version', '2023-06-01');

        if ($baseUrl === '' || $token === '') {
            return new FixProposal('Anthropic not configured.', [], ['Missing Anthropic base_url or token.']);
        }

        $response = Http::timeout($timeout)
            ->withHeaders([
                'x-api-key' => $token,
                'anthropic-version' => $version,
            ])
            ->post($baseUrl . '/messages', [
                'model' => $model,
                'max_tokens' => 1400,
                'system' => 'You are a secure PHP/Laravel fixer. Return strict JSON only.',
                'messages' => [
                    ['role' => 'user', 'content' => $this->buildPrompt($errorContext)],
                ],
            ]);

        if (!$response->successful()) {
            return new FixProposal('Anthropic request failed.', [], ['HTTP ' . $response->status()]);
        }

        $contentItems = (array) data_get($response->json(), 'content', []);
        $text = '';

        foreach ($contentItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            if ((string) ($item['type'] ?? '') !== 'text') {
                continue;
            }

            $text .= (string) ($item['text'] ?? '');
        }

        if ($text === '') {
            return new FixProposal('Invalid model response.', [], ['Model returned empty content.']);
        }

        return $this->proposalFromJsonContent($text);
    }
}
