<?php

declare(strict_types=1);

namespace SelfHealLM\Infrastructure\HuggingFace;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Http;
use SelfHealLM\Contracts\LLMClientInterface;
use SelfHealLM\Domain\Error\ErrorContext;
use SelfHealLM\Domain\Fix\FixProposal;
use SelfHealLM\Infrastructure\LLM\BuildsFixProposal;

final class HuggingFaceClient implements LLMClientInterface
{
    use BuildsFixProposal;

    public function __construct(private readonly Repository $config)
    {
    }

    public function proposeFix(ErrorContext $errorContext): FixProposal
    {
        $baseUrl = rtrim((string) $this->config->get('self-heal.huggingface.base_url', ''), '/');
        $token = (string) $this->config->get('self-heal.huggingface.token', '');
        $model = (string) $this->config->get('self-heal.huggingface.model', '');
        $timeout = (int) $this->config->get('self-heal.huggingface.timeout', 30);

        if ($baseUrl === '' || $token === '' || $model === '') {
            return new FixProposal('Hugging Face not configured.', [], ['Missing Hugging Face base_url, token, or model.']);
        }

        $response = Http::timeout($timeout)
            ->withToken($token)
            ->post($baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a secure PHP/Laravel fixer. Return strict JSON only.'],
                    ['role' => 'user', 'content' => $this->buildPrompt($errorContext)],
                ],
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
            ]);

        if (!$response->successful()) {
            return new FixProposal('Hugging Face request failed.', [], ['HTTP ' . $response->status()]);
        }

        $content = (string) data_get($response->json(), 'choices.0.message.content', '');

        return $this->proposalFromJsonContent($content);
    }
}
