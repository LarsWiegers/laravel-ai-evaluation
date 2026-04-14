<?php

declare(strict_types=1);

use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\EvalCaseBuilder;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\EvalRunner;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Judge\JudgeClient;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Judge\JudgeVerdict;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Scoring\ContainsScorer;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Scoring\ExactScorer;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Scoring\JudgeScorer;

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

it('passes judge expectation when score meets threshold', function () {
    $runner = new EvalRunner(
        judgeScorer: new JudgeScorer(
            new class implements JudgeClient {
                public function evaluate(string $input, string $actualOutput, string $criteria, ?string $reference = null, object|string|null $judge = null): JudgeVerdict
                {
                    return new JudgeVerdict(0.92, 'Output aligns with the reference answer.');
                }
            },
            0.7,
        ),
    );

    $agent = new class {
        public function prompt(string $prompt): string
        {
            return 'Refunds are available within 30 days.';
        }
    };

    $result = $runner->run(
        agent: $agent,
        caseId: 'judge-pass',
        input: 'What is your refund policy?',
        judgeExpectations: [[
            'criteria' => 'The answer should match policy and mention a clear time window.',
            'reference' => 'Refunds are available within 30 days.',
            'threshold' => 0.8,
        ]],
    );

    expect($result->passed())->toBeTrue();
    expect($result->expectationResults())->toHaveCount(1);
    expect($result->expectationResults()[0]['type'])->toBe('judge');
});

it('fails judge expectation when score is below threshold', function () {
    $runner = new EvalRunner(
        judgeScorer: new JudgeScorer(
            new class implements JudgeClient {
                public function evaluate(string $input, string $actualOutput, string $criteria, ?string $reference = null, object|string|null $judge = null): JudgeVerdict
                {
                    return new JudgeVerdict(0.35, 'The output misses key refund constraints.');
                }
            },
            0.7,
        ),
    );

    $agent = new class {
        public function prompt(string $prompt): string
        {
            return 'You can maybe get a refund.';
        }
    };

    $result = $runner->run(
        agent: $agent,
        caseId: 'judge-fail',
        input: 'What is your refund policy?',
        judgeExpectations: [[
            'criteria' => 'Answer must include exact refund window and conditions.',
            'reference' => 'Refunds are available within 30 days.',
            'threshold' => 0.8,
        ]],
    );

    expect($result->passed())->toBeFalse();
    expect($result->failures())->toHaveCount(1);
    expect($result->failures()[0])->toContain('Judge expectation failed');
});

it('builder supports expectJudgeAgainst', function () {
    $runner = new EvalRunner(
        judgeScorer: new JudgeScorer(
            new class implements JudgeClient {
                public function evaluate(string $input, string $actualOutput, string $criteria, ?string $reference = null, object|string|null $judge = null): JudgeVerdict
                {
                    expect($reference)->toBe('Refunds are available within 30 days.');

                    return new JudgeVerdict(0.85, 'Matches expected policy response.');
                }
            },
            0.7,
        ),
    );

    $builder = new EvalCaseBuilder(new class {
        public function prompt(string $prompt): string
        {
            return 'Refunds are available within 30 days.';
        }
    }, $runner);

    $result = $builder
        ->input('What is your refund policy?')
        ->expectJudgeAgainst(
            reference: 'Refunds are available within 30 days.',
            criteria: 'Answer must be policy accurate.',
            threshold: 0.8,
        )
        ->run();

    expect($result->passed())->toBeTrue();
});

it('passes explicit judge into expectJudgeAgainst', function () {
    $runner = new EvalRunner(
        judgeScorer: new JudgeScorer(
            new class implements JudgeClient {
                public function evaluate(string $input, string $actualOutput, string $criteria, ?string $reference = null, object|string|null $judge = null): JudgeVerdict
                {
                    expect($judge)->toBeInstanceOf(InlineJudgeAgent::class);

                    return new JudgeVerdict(0.9, 'Custom judge accepted response.');
                }
            },
            0.7,
        ),
    );

    $builder = new EvalCaseBuilder(new class {
        public function prompt(string $prompt): string
        {
            return 'Refunds are available within 30 days.';
        }
    }, $runner);

    $result = $builder
        ->input('What is your refund policy?')
        ->expectJudgeAgainst(
            reference: 'Refunds are available within 30 days.',
            criteria: 'Answer must be policy accurate.',
            threshold: 0.8,
            judge: new InlineJudgeAgent,
        )
        ->run();

    expect($result->passed())->toBeTrue();
});

class InlineJudgeAgent {}
