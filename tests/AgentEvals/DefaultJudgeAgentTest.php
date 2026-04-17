<?php

declare(strict_types=1);

use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Judge\DefaultJudgeAgent;

it('prompts through laravel ai anonymous agent fallback', function () {
    if (! class_exists('Laravel\\Ai\\AnonymousAgent')) {
        eval('namespace Laravel\\Ai; class AnonymousAgent { public function __construct(public string $instructions = "", public iterable $messages = [], public iterable $tools = []) {} public function prompt(string $prompt): string { return "stub:{$prompt}"; } }');
    }

    $judge = new DefaultJudgeAgent;

    expect($judge->prompt('judge this'))->toBe('stub:judge this');
});
