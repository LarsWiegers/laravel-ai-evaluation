<?php

declare(strict_types=1);

use LaravelAIEvaluation\Evaluation\EvalResult;
use LaravelAIEvaluation\Evaluation\EvalRunSummary;

it('prints text summary with pass fail and token totals', function () {
    $summary = app(EvalRunSummary::class);
    $summary->resetForTests();
    config()->set('laravel-ai-evaluation.summary.enabled', true);
    config()->set('laravel-ai-evaluation.summary.format', 'text');
    config()->set('laravel-ai-evaluation.summary.currency', 'USD');

    $summary->record(new EvalResult(
        name: 'one',
        input: 'a',
        output: 'b',
        failures: [],
        expectationResults: [],
        usage: ['prompt_tokens' => 10, 'completion_tokens' => 4, 'total_tokens' => 14, 'cost' => 0.001],
    ));

    $summary->record(new EvalResult(
        name: 'two',
        input: 'a',
        output: 'b',
        failures: ['failed'],
        expectationResults: [],
        usage: ['prompt_tokens' => 6, 'completion_tokens' => 3, 'total_tokens' => 9, 'cost' => 0.0025],
    ));

    $lines = [];
    $summary->flush(function (string $line) use (&$lines): void {
        $lines[] = $line;
    });

    $output = implode("\n", $lines);
    expect($output)->toContain('AI Eval Summary');
    expect($output)->toContain('Total: 2');
    expect($output)->toContain('Passed: 1');
    expect($output)->toContain('Failed: 1');
    expect($output)->toContain('Total tokens: 23');
    expect($output)->toContain('Estimated cost: USD 0.003500');

    $summary->resetForTests();
    config()->set('laravel-ai-evaluation.summary.enabled', false);
});

it('prints json summary when configured', function () {
    $summary = app(EvalRunSummary::class);
    $summary->resetForTests();
    config()->set('laravel-ai-evaluation.summary.enabled', true);
    config()->set('laravel-ai-evaluation.summary.format', 'json');
    config()->set('laravel-ai-evaluation.summary.currency', 'EUR');

    $summary->record(new EvalResult(
        name: 'one',
        input: 'a',
        output: 'b',
        failures: [],
        expectationResults: [],
        usage: ['prompt_tokens' => 2, 'completion_tokens' => 1, 'total_tokens' => 3, 'cost' => 0.01],
    ));

    $lines = [];
    $summary->flush(function (string $line) use (&$lines): void {
        $lines[] = $line;
    });

    expect($lines)->toHaveCount(1);
    $payload = json_decode($lines[0], true);

    expect($payload)->toBeArray();
    expect($payload['type'])->toBe('ai_eval_summary');
    expect($payload['total'])->toBe(1);
    expect($payload['total_tokens'])->toBe(3);
    expect($payload['currency'])->toBe('EUR');

    $summary->resetForTests();
    config()->set('laravel-ai-evaluation.summary.enabled', false);
});

it('flushes summary output only once per recorded dataset', function () {
    $summary = app(EvalRunSummary::class);
    $summary->resetForTests();
    config()->set('laravel-ai-evaluation.summary.enabled', true);
    config()->set('laravel-ai-evaluation.summary.format', 'text');

    $summary->record(new EvalResult(
        name: 'single',
        input: 'a',
        output: 'b',
        failures: [],
        expectationResults: [],
    ));

    $lines = [];
    $summary->flush(function (string $line) use (&$lines): void {
        $lines[] = $line;
    });

    $summary->flush(function (string $line) use (&$lines): void {
        $lines[] = $line;
    });

    expect($lines)->toHaveCount(8);

    $summary->resetForTests();
    config()->set('laravel-ai-evaluation.summary.enabled', false);
});
