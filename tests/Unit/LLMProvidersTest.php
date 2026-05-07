<?php

declare(strict_types=1);

namespace SelfHealLM\Tests\Unit;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SelfHealLM\Contracts\LLMClientInterface;
use SelfHealLM\Infrastructure\Anthropic\AnthropicClient;
use SelfHealLM\Infrastructure\HuggingFace\HuggingFaceClient;
use SelfHealLM\Infrastructure\Ollama\OllamaClient;
use SelfHealLM\Infrastructure\OpenAI\OpenAIClient;
use SelfHealLM\Tests\TestCase;

final class LLMProvidersTest extends TestCase
{
    public function test_it_resolves_openai_provider_by_default(): void
    {
        config()->set('self-heal.llm.provider', null);
        $this->app->forgetInstance(LLMClientInterface::class);

        self::assertInstanceOf(OpenAIClient::class, app(LLMClientInterface::class));
    }

    public function test_it_resolves_configured_provider(): void
    {
        config()->set('self-heal.llm.provider', 'anthropic');
        $this->app->forgetInstance(LLMClientInterface::class);
        self::assertInstanceOf(AnthropicClient::class, app(LLMClientInterface::class));

        config()->set('self-heal.llm.provider', 'huggingface');
        $this->app->forgetInstance(LLMClientInterface::class);
        self::assertInstanceOf(HuggingFaceClient::class, app(LLMClientInterface::class));

        config()->set('self-heal.llm.provider', 'ollama');
        $this->app->forgetInstance(LLMClientInterface::class);
        self::assertInstanceOf(OllamaClient::class, app(LLMClientInterface::class));
    }

    public function test_it_falls_back_to_openai_for_unknown_provider(): void
    {
        config()->set('self-heal.llm.provider', 'unknown-provider');
        $this->app->forgetInstance(LLMClientInterface::class);

        self::assertInstanceOf(OpenAIClient::class, app(LLMClientInterface::class));
    }

    public function test_anthropic_client_returns_fix_proposal_from_json_content(): void
    {
        config()->set('self-heal.anthropic.base_url', 'https://api.anthropic.com/v1');
        config()->set('self-heal.anthropic.token', 'token');
        config()->set('self-heal.anthropic.model', 'claude-3-5-sonnet-latest');
        config()->set('self-heal.anthropic.timeout', 30);
        config()->set('self-heal.anthropic.version', '2023-06-01');

        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [[
                    'type' => 'text',
                    'text' => '{"summary":"Fix from Anthropic","warnings":[],"operations":[{"file_path":"app/Test.php","find":"old","replace":"new"}]}',
                ]],
            ], 200),
        ]);

        $proposal = app(AnthropicClient::class)->proposeFix($this->sampleContext());

        self::assertSame('Fix from Anthropic', $proposal->summary);
        self::assertCount(1, $proposal->operations);
        self::assertSame('app/Test.php', $proposal->operations[0]->filePath);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.anthropic.com/v1/messages');
    }

    public function test_huggingface_client_reports_invalid_payload(): void
    {
        config()->set('self-heal.huggingface.base_url', 'https://router.huggingface.co/v1');
        config()->set('self-heal.huggingface.token', 'hf_token');
        config()->set('self-heal.huggingface.model', 'openai/gpt-oss-120b');
        config()->set('self-heal.huggingface.timeout', 30);

        Http::fake([
            'https://router.huggingface.co/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'not json']]],
            ], 200),
        ]);

        $proposal = app(HuggingFaceClient::class)->proposeFix($this->sampleContext());

        self::assertSame('Invalid model response.', $proposal->summary);
        self::assertSame(['Model returned non-JSON payload.'], $proposal->warnings);
    }

    public function test_ollama_client_returns_configuration_error_when_model_missing(): void
    {
        config()->set('self-heal.ollama.base_url', 'http://localhost:11434/v1');
        config()->set('self-heal.ollama.model', '');

        $proposal = app(OllamaClient::class)->proposeFix($this->sampleContext());

        self::assertSame('Ollama not configured.', $proposal->summary);
        self::assertSame(['Missing Ollama base_url or model.'], $proposal->warnings);
    }

    private function sampleContext(): \SelfHealLM\Domain\Error\ErrorContext
    {
        return new \SelfHealLM\Domain\Error\ErrorContext(
            'Test exception',
            'app/Test.php',
            10,
            [],
            '$a = 1;',
            '<?php $a = 1;',
            '[2026-05-05] local.ERROR: Test exception'
        );
    }
}
