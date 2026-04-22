<?php

declare(strict_types=1);

namespace LaravelAIEvaluation;

use Illuminate\Support\ServiceProvider;
use LaravelAIEvaluation\Console\InstallAgentEvalsCommand;
use LaravelAIEvaluation\Console\PestProcessRunner;
use LaravelAIEvaluation\Console\MakeAgentEvalCommand;
use LaravelAIEvaluation\Console\RunAgentEvalsCommand;
use LaravelAIEvaluation\Console\StandaloneEvalRunner;
use LaravelAIEvaluation\Evaluation\EvalRunner;
use LaravelAIEvaluation\Evaluation\EvalRunSummary;
use LaravelAIEvaluation\Evaluation\Judge\JudgeClient;
use LaravelAIEvaluation\Evaluation\Judge\PromptJudgeClient;
use LaravelAIEvaluation\Evaluation\Scoring\ContainsScorer;
use LaravelAIEvaluation\Evaluation\Scoring\ExactScorer;
use LaravelAIEvaluation\Evaluation\Scoring\JudgeScorer;
use LaravelAIEvaluation\Evaluation\Support\PromptingTargetResolver;
use LaravelAIEvaluation\Evaluation\Support\ResponseNormalizer;

class LaravelAIEvaluationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laravel-ai-evaluation.php' => config_path('laravel-ai-evaluation.php'),
            ], 'laravel-ai-evaluation-config');

            $this->commands([
                InstallAgentEvalsCommand::class,
                MakeAgentEvalCommand::class,
                RunAgentEvalsCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-ai-evaluation.php', 'laravel-ai-evaluation');

        $this->app->singleton(StandaloneEvalRunner::class, function () {
            return new StandaloneEvalRunner;
        });

        $this->app->bind(JudgeClient::class, PromptJudgeClient::class);

        $this->app->singleton(ResponseNormalizer::class, function () {
            return new ResponseNormalizer;
        });

        $this->app->singleton(PromptingTargetResolver::class, function () {
            return new PromptingTargetResolver;
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
                targetResolver: $this->app->make(PromptingTargetResolver::class),
            );
        });

        $this->app->singleton(EvalRunSummary::class, function () {
            return new EvalRunSummary;
        });
    }
}
