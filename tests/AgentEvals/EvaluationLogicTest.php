<?php

declare(strict_types=1);

use LaravelAIEvaluation\Contracts\EvalExpectation;
use LaravelAIEvaluation\Evaluation\EvalCaseBuilder;
use LaravelAIEvaluation\Evaluation\EvalRunner;
use LaravelAIEvaluation\Evaluation\ExpectationResult;
use LaravelAIEvaluation\Evaluation\Judge\JudgeClient;
use LaravelAIEvaluation\Evaluation\Judge\JudgeVerdict;
use LaravelAIEvaluation\Evaluation\Scoring\ContainsScorer;
use LaravelAIEvaluation\Evaluation\Scoring\ExactScorer;
use LaravelAIEvaluation\Evaluation\Scoring\JudgeScorer;
use LaravelAIEvaluation\Standalone\StandaloneEvalContext;

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
})->throws(RuntimeException::class, 'must implement Laravel\\Ai\\Contracts\\Agent or expose a prompt(string $prompt) method');

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
        ->name('contains-combine')
        ->input('ignored')
        ->expectContains('alpha')
        ->expectContains(['beta', 'gamma'])
        ->run();

    expect($result->passed())->toBeTrue();
});

it('builder supports passing deterministic expectations', function () {
    $builder = new EvalCaseBuilder(new class {
        public function prompt(string $prompt): string
        {
            return '{"status":"eligible","policy":{"days":30},"items":[{"name":"refund"}],"message":"Refunds within 30 days."}';
        }
    });

    $result = $builder
        ->name('deterministic-pass')
        ->input('ignored')
        ->expectRegex('/refunds? within \d+ days/i')
        ->expectNotContains(['legal guarantee', 'always approved'])
        ->expectJson()
        ->expectJsonPath('status', 'eligible')
        ->expectJsonPath('policy.days', 30)
        ->expectJsonPath('items.0.name')
        ->expectLength(min: 20, max: 200)
        ->expectStartsWith('{')
        ->expectEndsWith('}')
        ->run();

    expect($result->passed())->toBeTrue();
    expect(array_column($result->expectationResults(), 'type'))->toBe([
        'regex',
        'not_contains',
        'json',
        'json_path',
        'json_path',
        'json_path',
        'length',
        'starts_with',
        'ends_with',
    ]);
});

it('builder reports failing deterministic expectations with clear messages', function () {
    $builder = new EvalCaseBuilder(new class {
        public function prompt(string $prompt): string
        {
            return 'Legal guarantee is always approved.';
        }
    });

    $result = $builder
        ->name('deterministic-fail')
        ->input('ignored')
        ->expectRegex('/refunds? within \d+ days/i')
        ->expectNotContains(['Legal guarantee', 'always approved'])
        ->expectJson()
        ->expectJsonPath('status', 'eligible')
        ->expectLength(max: 10)
        ->expectStartsWith('{')
        ->expectEndsWith('}')
        ->run();

    expect($result->passed())->toBeFalse();
    expect($result->failures())->toHaveCount(7);
    expect($result->failures()[0])->toContain('Output did not match regex pattern');
    expect($result->failures()[1])->toContain('Output contained forbidden substring(s): Legal guarantee, always approved');
    expect($result->failures()[2])->toContain('Output is not valid JSON');
    expect($result->failures()[3])->toContain('JSON path "status" could not be checked');
    expect($result->failures()[4])->toContain('Expected output length to be at most 10 characters');
    expect($result->failures()[5])->toContain('Expected output to start with "{"');
    expect($result->failures()[6])->toContain('Expected output to end with "}"');
});

it('json path expectations distinguish missing paths from expected null values', function () {
    $builder = new EvalCaseBuilder(new class {
        public function prompt(string $prompt): string
        {
            return '{"status":null}';
        }
    });

    $result = $builder
        ->name('json-path-null')
        ->input('ignored')
        ->expectJsonPath('status', null)
        ->expectJsonPath('missing')
        ->run();

    expect($result->passed())->toBeFalse();
    expect($result->expectationResults()[0]['passed'])->toBeTrue();
    expect($result->expectationResults()[1]['reason'])->toBe('JSON path "missing" was not found.');
});

it('builder supports passing custom closure expectations', function () {
    $builder = new EvalCaseBuilder(new class {
        public function prompt(string $prompt): string
        {
            return 'Refunds are available within 30 days.';
        }
    });

    $result = $builder
        ->name('custom-closure-pass')
        ->input('ignored')
        ->expect(fn (string $output): bool => str_contains($output, '30 days'))
        ->run();

    expect($result->passed())->toBeTrue();
    expect($result->expectationResults()[0])->toMatchArray([
        'type' => 'custom',
        'name' => 'Closure',
        'passed' => true,
        'reason' => 'Returned true.',
    ]);
});

it('builder supports failing custom expectation contract objects', function () {
    $builder = new EvalCaseBuilder(new class {
        public function prompt(string $prompt): string
        {
            return 'Refunds may be available.';
        }
    });

    $result = $builder
        ->name('custom-contract-fail')
        ->input('ignored')
        ->expect(new RefundPolicyExpectation)
        ->run();

    expect($result->passed())->toBeFalse();
    expect($result->failures()[0])->toContain('Custom expectation "RefundPolicyExpectation" failed: Missing 30-day refund window.');
    expect($result->expectationResults()[0])->toMatchArray([
        'type' => 'custom',
        'name' => RefundPolicyExpectation::class,
        'passed' => false,
        'reason' => 'Missing 30-day refund window.',
        'score' => 0.25,
        'metadata' => ['required_window' => '30 days'],
    ]);
});

it('builder resolves invokable custom expectation class strings from the container', function () {
    $builder = new EvalCaseBuilder(new class {
        public function prompt(string $prompt): string
        {
            return 'Refunds are available within 30 days.';
        }
    });

    $result = $builder
        ->name('custom-container-pass')
        ->input('ignored')
        ->expect(ContainerResolvedRefundExpectation::class)
        ->run();

    expect($result->passed())->toBeTrue();
    expect($result->expectationResults()[0])->toMatchArray([
        'type' => 'custom',
        'name' => ContainerResolvedRefundExpectation::class,
        'passed' => true,
        'reason' => 'Output includes refund policy window.',
        'score' => 1.0,
        'metadata' => ['days' => 30],
    ]);
});

it('builder records custom expectation exceptions as failures', function () {
    $builder = new EvalCaseBuilder(new class {
        public function prompt(string $prompt): string
        {
            return 'Refunds are available within 30 days.';
        }
    });

    $result = $builder
        ->name('custom-exception-fail')
        ->input('ignored')
        ->expect(function (string $output): bool {
            throw new RuntimeException('custom scorer exploded');
        })
        ->run();

    expect($result->passed())->toBeFalse();
    expect($result->expectationResults()[0])->toMatchArray([
        'type' => 'custom',
        'name' => 'Closure',
        'passed' => false,
        'metadata' => ['exception_class' => RuntimeException::class],
    ]);
    expect($result->expectationResults()[0]['reason'])->toContain('custom scorer exploded');
    expect($result->failures()[0])->toContain('Custom expectation "Closure" failed: Threw RuntimeException: custom scorer exploded');
});

it('builder captures test file location on result', function () {
    $builder = new EvalCaseBuilder(new class {
        public function prompt(string $prompt): string
        {
            return 'alpha beta gamma';
        }
    });

    $result = $builder
        ->name('captures-location')
        ->input('ignored')
        ->expectContains('alpha')
        ->run();

    expect($result->location())->toContain('tests/AgentEvals/EvaluationLogicTest.php');
});

it('uses standalone suite name when explicit name is omitted', function () {
    $runner = new EvalRunner;
    $agent = new class {
        public function prompt(string $prompt): string
        {
            return 'alpha beta gamma';
        }
    };

    $result = StandaloneEvalContext::withName('suite-name-eval', function () use ($runner, $agent) {
        return $runner->run(
            agent: $agent,
            name: null,
            input: 'ignored',
            contains: ['alpha'],
        );
    });

    expect($result->toArray()['name'])->toBe('suite-name-eval');
});

it('builder supports explicit location override', function () {
    $builder = new EvalCaseBuilder(new class {
        public function prompt(string $prompt): string
        {
            return 'alpha beta gamma';
        }
    });

    $result = $builder
        ->name('explicit-location')
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
        name: 'judge-pass',
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
        name: 'judge-fail',
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
        name: 'auth-error',
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
        name: 'retry-agent',
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
            name: 'no-retry-agent',
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
        name: 'retry-judge',
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

class RefundPolicyExpectation implements EvalExpectation
{
    public function evaluate(string $input, string $output): ExpectationResult
    {
        if (str_contains($output, '30 days')) {
            return ExpectationResult::pass('Output includes 30-day refund window.', 1.0, ['required_window' => '30 days']);
        }

        return ExpectationResult::fail('Missing 30-day refund window.', 0.25, ['required_window' => '30 days']);
    }
}

class RefundPolicyWindow
{
    public function days(): int
    {
        return 30;
    }
}

class ContainerResolvedRefundExpectation
{
    public function __construct(protected RefundPolicyWindow $window) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $output): array
    {
        $expected = sprintf('%d days', $this->window->days());

        return [
            'passed' => str_contains($output, $expected),
            'reason' => 'Output includes refund policy window.',
            'score' => 1.0,
            'metadata' => ['days' => $this->window->days()],
        ];
    }
}
