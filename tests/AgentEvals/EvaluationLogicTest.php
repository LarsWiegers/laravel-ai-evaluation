<?php

declare(strict_types=1);

use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\EvalCaseBuilder;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\EvalRunner;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Scoring\ContainsScorer;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Scoring\ExactScorer;

it('contains scorer returns missing substrings', function () {
    $scorer = new ContainsScorer;

    $missing = $scorer->missing('hello world', ['hello', 'planet']);

    expect($missing)->toBe(['planet']);
});

it('exact scorer compares trimmed values', function () {
    $scorer = new ExactScorer;

    expect($scorer->matches("  OK\n", 'OK'))->toBeTrue();
});

it('runner throws when no expectations are defined', function () {
    $runner = new EvalRunner;
    $agent = new class {
        public function prompt(string $prompt): string
        {
            return 'anything';
        }
    };

    $runner->run($agent, 'missing-expectations', 'input');
})->throws(RuntimeException::class, "must define at least one expectation");

it('runner throws when agent has no prompt method', function () {
    $runner = new EvalRunner;

    $runner->run(new stdClass, 'invalid-agent', 'input', ['x']);
})->throws(RuntimeException::class, 'agent must implement a prompt method');

it('runner supports stringable object responses', function () {
    $runner = new EvalRunner;
    $agent = new class {
        public function prompt(string $prompt): object
        {
            return new class {
                public function __toString(): string
                {
                    return 'Hello from object';
                }
            };
        }
    };

    $result = $runner->run($agent, 'stringable-response', 'input', ['Hello']);

    expect($result->passed())->toBeTrue();
});

it('runner supports response objects with text property', function () {
    $runner = new EvalRunner;
    $agent = new class {
        public function prompt(string $prompt): object
        {
            return new class {
                public string $text = 'text property output';
            };
        }
    };

    $result = $runner->run($agent, 'text-property-response', 'input', ['property output']);

    expect($result->passed())->toBeTrue();
});

it('builder combines contains expectations from string and array', function () {
    $builder = new EvalCaseBuilder(new class {
        public function prompt(string $prompt): string
        {
            return 'alpha beta gamma';
        }
    });

    $result = $builder
        ->case('contains-combine')
        ->input('ignored')
        ->expectContains('alpha')
        ->expectContains(['beta', 'gamma'])
        ->run();

    expect($result->passed())->toBeTrue();
});

it('result includes both exact and contains failures when both fail', function () {
    $runner = new EvalRunner;
    $agent = new class {
        public function prompt(string $prompt): string
        {
            return 'actual response';
        }
    };

    $result = $runner->run(
        $agent,
        'combined-failure',
        'input',
        ['missing-substring'],
        'expected response'
    );

    expect($result->passed())->toBeFalse();
    expect($result->failures())->toHaveCount(2);
});
