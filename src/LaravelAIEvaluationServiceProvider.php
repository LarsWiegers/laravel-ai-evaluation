<?php

declare(strict_types=1);

namespace LaravelAIEvaluation;

use Illuminate\Support\ServiceProvider;
use LaravelAIEvaluation\Console\PestProcessRunner;
use LaravelAIEvaluation\Console\RunAgentEvalsCommand;
use LaravelAIEvaluation\Console\StandaloneEvalRunner;
use LaravelAIEvaluation\Evaluation\EvalRunner;
use LaravelAIEvaluation\Evaluation\EvalRunSummary;
use LaravelAIEvaluation\Evaluation\Judge\JudgeClient;
use LaravelAIEvaluation\Evaluation\Judge\PromptJudgeClient;
use LaravelAIEvaluation\Evaluation\Scoring\ContainsScorer;
use LaravelAIEvaluation\Evaluation\Scoring\ExactScorer;
use LaravelAIEvaluation\Evaluation\Scoring\JudgeScorer;
use LaravelAIEvaluation\Evaluation\Support\ResponseNormalizer;

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

        $this->app->singleton(PestProcessRunner::class, function () {
            return new PestProcessRunner;
        });

        $this->app->bind(JudgeClient::class, PromptJudgeClient::class);

        $this->app->singleton(ResponseNormalizer::class, function () {
            return new ResponseNormalizer;
        });

        $this->app->singleton(JudgeScorer::class, function () {
            return new JudgeScorer(
                $this->app->make(JudgeClient::class),
                (float) config('laravel-ai-evaluation.judge.threshold', 0.7),
            );
        });

        $this->app->singleton(EvalRunner::class, function () {
            return new EvalRunner(
                containsScorer: $this->app->make(ContainsScorer::class),
                exactScorer: $this->app->make(ExactScorer::class),
                judgeScorer: $this->app->make(JudgeScorer::class),
                runSummary: $this->app->make(EvalRunSummary::class),
                responseNormalizer: $this->app->make(ResponseNormalizer::class),
            );
        });

        $this->app->singleton(EvalRunSummary::class, function () {
            return new EvalRunSummary;
        });
    }
}
