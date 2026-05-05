<?php

declare(strict_types=1);

namespace SelfHealLM;

use Illuminate\Support\ServiceProvider;
use SelfHealLM\Application\SelfHealPipeline;
use SelfHealLM\Console\RunSelfHealCommand;
use SelfHealLM\Contracts\LLMClientInterface;
use SelfHealLM\Contracts\ReporterInterface;
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

        $this->app->singleton(LLMClientInterface::class, OpenAIClient::class);
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
