<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\LaravelAIEvaluation;

use Illuminate\Support\ServiceProvider;
use LaravelAIEvaluation\LaravelAIEvaluation\Console\PestProcessRunner;
use LaravelAIEvaluation\LaravelAIEvaluation\Console\RunAgentEvalsCommand;

class LaravelAIEvaluationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-ai-evaluation.php'),
            ], 'config');

            $this->commands([
                RunAgentEvalsCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-ai-evaluation');

        $this->app->singleton('laravel-ai-evaluation', function () {
            return new LaravelAIEvaluation;
        });

        $this->app->singleton(PestProcessRunner::class, function () {
            return new PestProcessRunner;
        });
    }
}
