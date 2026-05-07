<?php

declare(strict_types=1);

namespace SelfHealLM;

use Illuminate\Support\ServiceProvider;
use SelfHealLM\Application\SelfHealPipeline;
use SelfHealLM\Console\RunSelfHealCommand;
use SelfHealLM\Contracts\LLMClientInterface;
use SelfHealLM\Contracts\ReporterInterface;
use SelfHealLM\Infrastructure\Anthropic\AnthropicClient;
use SelfHealLM\Infrastructure\HuggingFace\HuggingFaceClient;
use SelfHealLM\Infrastructure\Ollama\OllamaClient;
use SelfHealLM\Infrastructure\OpenAI\OpenAIClient;
use SelfHealLM\Infrastructure\Reporting\CompositeReporter;
use SelfHealLM\Infrastructure\Reporting\FileReporter;
use SelfHealLM\Infrastructure\Reporting\SentryReporter;
use SelfHealLM\Infrastructure\Reporting\SlackReporter;
use SelfHealLM\Infrastructure\Reporting\TelegramReporter;
use SelfHealLM\Infrastructure\Reporting\WebhookReporter;

final class LaravelSelfHealServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/self-heal.php', 'self-heal');

        $this->app->singleton(OpenAIClient::class);
        $this->app->singleton(AnthropicClient::class);
        $this->app->singleton(HuggingFaceClient::class);
        $this->app->singleton(OllamaClient::class);
        $this->app->singleton(LLMClientInterface::class, function (): LLMClientInterface {
            $provider = (string) config('self-heal.llm.provider', 'openai');

            return match ($provider) {
                'openai' => $this->app->make(OpenAIClient::class),
                'anthropic' => $this->app->make(AnthropicClient::class),
                'huggingface' => $this->app->make(HuggingFaceClient::class),
                'ollama' => $this->app->make(OllamaClient::class),
                default => $this->app->make(OpenAIClient::class),
            };
        });
        $this->app->singleton(TelegramReporter::class);
        $this->app->singleton(SlackReporter::class);
        $this->app->singleton(WebhookReporter::class);
        $this->app->singleton(SentryReporter::class);
        $this->app->singleton(FileReporter::class);
        $this->app->singleton(ReporterInterface::class, function (): ReporterInterface {
            return new CompositeReporter([
                $this->app->make(TelegramReporter::class),
                $this->app->make(SlackReporter::class),
                $this->app->make(WebhookReporter::class),
                $this->app->make(SentryReporter::class),
                $this->app->make(FileReporter::class),
            ]);
        });
        $this->app->singleton(SelfHealPipeline::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/self-heal.php' => config_path('self-heal.php'),
        ], 'self-heal-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                RunSelfHealCommand::class,
            ]);
        }
    }
}
