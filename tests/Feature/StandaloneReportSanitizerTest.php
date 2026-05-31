<?php

declare(strict_types=1);

use LaravelAIEvaluation\Console\Reports\StandaloneReportSanitizer;

it('sanitizes nested string values and ignores invalid redact patterns', function () {
    config()->set('laravel-ai-evaluation.standalone.report.max_failure_length', 500);
    config()->set('laravel-ai-evaluation.standalone.report.redact_patterns', [
        '/token=[^\s]+/',
        '/unterminated',
        123,
    ]);

    $value = (new StandaloneReportSanitizer)->value([
        'message' => 'token=secret nested value',
        'nested' => ['token=another-secret'],
        'score' => 1.0,
    ]);

    expect($value)->toBe([
        'message' => '[REDACTED] nested value',
        'nested' => ['[REDACTED]'],
        'score' => 1.0,
    ]);
});

it('honors report inclusion flags and zero max lengths', function () {
    config()->set('laravel-ai-evaluation.standalone.report.include_input', true);
    config()->set('laravel-ai-evaluation.standalone.report.include_output', false);
    config()->set('laravel-ai-evaluation.standalone.report.max_input_length', 0);

    $sanitizer = new StandaloneReportSanitizer;

    expect($sanitizer->input('private prompt'))->toBe('... [truncated]');
    expect($sanitizer->output('visible output'))->toBeNull();
});

it('normalizes base path locations while preserving external paths', function () {
    $sanitizer = new StandaloneReportSanitizer;

    expect($sanitizer->location(base_path('tests/AgentEvals/Refund.eval.php').':12'))
        ->toBe('tests/AgentEvals/Refund.eval.php:12');

    expect($sanitizer->location('/tmp/outside.eval.php:7'))
        ->toBe('/tmp/outside.eval.php:7');
});
