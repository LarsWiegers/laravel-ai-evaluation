<?php

declare(strict_types=1);

namespace LaravelAIEvaluation;

use Illuminate\Support\ServiceProvider;
use LaravelAIEvaluation\Console\PestProcessRunner;
use LaravelAIEvaluation\Console\RunAgentEvalsCommand;
use LaravelAIEvaluation\Console\StandaloneEvalRunner;
use LaravelAIEvaluation\Evaluation\EvalRunner;
use LaravelAIEvaluation\Evaluation\EvalRunSummary;

class LaravelAIEvaluationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-ai-evaluation.php'),
            ], 'laravel-ai-evaluation-config');

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

        $this->app->singleton(StandaloneEvalRunner::class, function () {
            return new StandaloneEvalRunner;
        });

        $this->app->singleton(PestProcessRunner::class, function ($app) {
            return $app->make(StandaloneEvalRunner::class);
        });

        $this->app->singleton(EvalRunner::class, function () {
            return new EvalRunner;
        });

        $this->app->singleton(EvalRunSummary::class, function () {
            return new EvalRunSummary;
        });
    }
}
