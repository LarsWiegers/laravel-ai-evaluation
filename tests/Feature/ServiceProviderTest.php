<?php

declare(strict_types=1);

use LaravelAIEvaluation\Console\StandaloneEvalRunner;
use LaravelAIEvaluation\Evaluation\EvalRunner;
use LaravelAIEvaluation\Evaluation\EvalRunSummary;
use LaravelAIEvaluation\Evaluation\Judge\JudgeClient;
use LaravelAIEvaluation\Evaluation\Judge\JudgeVerdict;
use LaravelAIEvaluation\Evaluation\Judge\PromptJudgeClient;
use LaravelAIEvaluation\Evaluation\Scoring\JudgeScorer;
use LaravelAIEvaluation\Evaluation\Support\PromptingTargetResolver;
use LaravelAIEvaluation\Evaluation\Support\ResponseNormalizer;

it('registers core package services with expected lifetimes', function () {
    expect(app(StandaloneEvalRunner::class))->toBe(app(StandaloneEvalRunner::class));
    expect(app(ResponseNormalizer::class))->toBe(app(ResponseNormalizer::class));
    expect(app(PromptingTargetResolver::class))->toBe(app(PromptingTargetResolver::class));
    expect(app(EvalRunSummary::class))->toBe(app(EvalRunSummary::class));
    expect(app(EvalRunner::class))->toBe(app(EvalRunner::class));
    expect(app(JudgeClient::class))->toBeInstanceOf(PromptJudgeClient::class);
});

it('builds judge scorer from configured threshold and judge client binding', function () {
    config()->set('laravel-ai-evaluation.judge.threshold', 0.9);

    app()->bind(JudgeClient::class, static function () {
        return new class implements JudgeClient {
            public function evaluate(string $input, string $actualOutput, string $criteria, ?string $reference = null, object|string|null $judge = null): JudgeVerdict
            {
                return new JudgeVerdict(0.85, 'Close enough');
            }
        };
    });

    app()->forgetInstance(JudgeScorer::class);

    $result = app(JudgeScorer::class)->score('input', 'actual', 'criteria');

    expect($result['threshold'])->toBe(0.9);
    expect($result['passed'])->toBeFalse();
});
