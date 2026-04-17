<?php

declare(strict_types=1);

use LaravelAIEvaluation\Evaluation\EvalCaseBuilder;
use LaravelAIEvaluation\Evaluation\EvalRunner;
use LaravelAIEvaluation\Evaluation\Judge\JudgeClient;
use LaravelAIEvaluation\Evaluation\Judge\JudgeVerdict;
use LaravelAIEvaluation\Evaluation\Scoring\ContainsScorer;
use LaravelAIEvaluation\Evaluation\Scoring\ExactScorer;
use LaravelAIEvaluation\Evaluation\Scoring\JudgeScorer;

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

it('builder captures test file location on result', function () {
    $builder = new EvalCaseBuilder(new class {
        public function prompt(string $prompt): string
        {
            return 'alpha beta gamma';
        }
    });

    $result = $builder
        ->case('captures-location')
        ->input('ignored')
        ->expectContains('alpha')
        ->run();

    expect($result->location())->toContain('tests/AgentEvals/EvaluationLogicTest.php');
});

it('builder supports explicit location override', function () {
    $builder = new EvalCaseBuilder(new class {
        public function prompt(string $prompt): string
        {
            return 'alpha beta gamma';
        }
    });

    $result = $builder
        ->case('explicit-location')
        ->location('tests/AgentEvals/ExplicitLocationTest.php:12')
        ->input('ignored')
        ->expectContains('alpha')
        ->run();

    expect($result->location())->toBe('tests/AgentEvals/ExplicitLocationTest.php:12');
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

it('applies useJudge default for expectJudge and expectJudgeAgainst', function () {
    $runner = new EvalRunner(
        judgeScorer: new JudgeScorer(
            new class implements JudgeClient {
                public function evaluate(string $input, string $actualOutput, string $criteria, ?string $reference = null, object|string|null $judge = null): JudgeVerdict
                {
                    expect($judge)->toBeInstanceOf(InlineJudgeAgent::class);

                    return new JudgeVerdict(0.95, 'Default fluent judge was used.');
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
        ->useJudge(new InlineJudgeAgent)
        ->expectJudge('Answer must be policy accurate.', threshold: 0.8)
        ->expectJudgeAgainst(
            reference: 'Refunds are available within 30 days.',
            criteria: 'Answer must match reference.',
            threshold: 0.8,
        )
        ->run();

    expect($result->passed())->toBeTrue();
});

it('wraps 401 prompt failures with api key guidance', function () {
    $runner = new EvalRunner;
    $agent = new class {
        public function prompt(string $prompt): string
        {
            throw new RuntimeException('401 Unauthorized', 401);
        }
    };

    $runner->run(
        agent: $agent,
        caseId: 'auth-error',
        input: 'Hello',
        contains: ['ignored'],
    );
})->throws(RuntimeException::class, 'Authentication error. Check your AI provider API key is configured.');

it('retries transient agent prompt failures when configured', function () {
    $runner = new EvalRunner(retries: 1, retrySleepMs: 0);
    $agent = new class {
        public int $attempts = 0;

        public function prompt(string $prompt): string
        {
            $this->attempts++;

            if ($this->attempts === 1) {
                throw new RuntimeException('temporary provider timeout');
            }

            return 'retry succeeded';
        }
    };

    $result = $runner->run(
        agent: $agent,
        caseId: 'retry-agent',
        input: 'Hello',
        contains: ['retry succeeded'],
    );

    expect($result->passed())->toBeTrue();
    expect($agent->attempts)->toBe(2);
});

it('does not retry non transient agent failures', function () {
    $runner = new EvalRunner(retries: 3, retrySleepMs: 0);
    $agent = new class {
        public int $attempts = 0;

        public function prompt(string $prompt): string
        {
            $this->attempts++;

            throw new RuntimeException('Invalid response schema');
        }
    };

    expect(function () use ($runner, $agent): void {
        $runner->run(
            agent: $agent,
            caseId: 'no-retry-agent',
            input: 'Hello',
            contains: ['retry succeeded'],
        );
    })->toThrow(RuntimeException::class, 'Invalid response schema');

    expect($agent->attempts)->toBe(1);
});

it('retries transient judge failures when configured', function () {
    $judgeClient = new class implements JudgeClient {
        public int $attempts = 0;

        public function evaluate(string $input, string $actualOutput, string $criteria, ?string $reference = null, object|string|null $judge = null): JudgeVerdict
        {
            $this->attempts++;

            if ($this->attempts === 1) {
                throw new RuntimeException('temporary judge timeout');
            }

            return new JudgeVerdict(0.9, 'Recovered after retry.');
        }
    };

    $runner = new EvalRunner(
        judgeScorer: new JudgeScorer(
            $judgeClient,
            0.7,
        ),
        retries: 1,
        retrySleepMs: 0,
    );

    $agent = new class {
        public function prompt(string $prompt): string
        {
            return 'Refunds are available within 30 days.';
        }
    };

    $result = $runner->run(
        agent: $agent,
        caseId: 'retry-judge',
        input: 'What is your refund policy?',
        judgeExpectations: [[
            'criteria' => 'Answer should mention refund window.',
            'reference' => null,
            'threshold' => 0.8,
            'judge' => null,
        ]],
    );

    expect($result->passed())->toBeTrue();
    expect($judgeClient->attempts)->toBe(2);
});

class InlineJudgeAgent {}
