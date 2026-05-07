<?php

declare(strict_types=1);

namespace SelfHealLM\Infrastructure\Ollama;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Http;
use SelfHealLM\Contracts\LLMClientInterface;
use SelfHealLM\Domain\Error\ErrorContext;
use SelfHealLM\Domain\Fix\FixProposal;
use SelfHealLM\Infrastructure\LLM\BuildsFixProposal;

final class OllamaClient implements LLMClientInterface
{
    use BuildsFixProposal;

    public function __construct(private readonly Repository $config)
    {
    }

    public function proposeFix(ErrorContext $errorContext): FixProposal
    {
        $baseUrl = rtrim((string) $this->config->get('self-heal.ollama.base_url', ''), '/');
        $token = (string) $this->config->get('self-heal.ollama.token', '');
        $model = (string) $this->config->get('self-heal.ollama.model', '');
        $timeout = (int) $this->config->get('self-heal.ollama.timeout', 30);

        if ($baseUrl === '' || $model === '') {
            return new FixProposal('Ollama not configured.', [], ['Missing Ollama base_url or model.']);
        }

        $request = Http::timeout($timeout);
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->post($baseUrl . '/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a secure PHP/Laravel fixer. Return strict JSON only.'],
                ['role' => 'user', 'content' => $this->buildPrompt($errorContext)],
            ],
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (!$response->successful()) {
            return new FixProposal('Ollama request failed.', [], ['HTTP ' . $response->status()]);
        }

        $content = (string) data_get($response->json(), 'choices.0.message.content', '');

        return $this->proposalFromJsonContent($content);
    }
}
