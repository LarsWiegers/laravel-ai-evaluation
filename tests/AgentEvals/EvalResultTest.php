<?php

declare(strict_types=1);

use LaravelAIEvaluation\Evaluation\EvalResult;

it('assertPasses returns self when eval passes', function () {
    $result = new EvalResult(
        name: 'passing-case',
        input: 'hello',
        output: 'world',
        failures: [],
        expectationResults: [['type' => 'contains', 'passed' => true, 'reason' => 'ok']],
    );

    expect($result->assertPasses())->toBe($result);
});

it('assertPasses throws runtime exception when eval fails', function () {
    $result = new EvalResult(
        name: 'failing-case',
        input: 'hello',
        output: 'world',
        failures: ['Missing expected token'],
        expectationResults: [],
    );

    $result->assertPasses();
})->throws(PHPUnit\Framework\ExpectationFailedException::class, "AI eval 'failing-case' failed.");

it('dump includes judge score threshold and reason', function () {
    $result = new EvalResult(
        name: 'judge-case',
        input: 'question',
        output: 'answer',
        failures: [],
        expectationResults: [[
            'type' => 'judge',
            'passed' => true,
            'score' => 0.85,
            'threshold' => 0.7,
            'reason' => 'Looks good.',
        ]],
    );

    $lines = [];
    $result->dump(function (string $line) use (&$lines): void {
        $lines[] = $line;
    });

    expect(implode("\n", $lines))->toContain('score=0.850 threshold=0.700 reason="Looks good."');
});

it('dump includes location when available', function () {
    $result = new EvalResult(
        name: 'location-case',
        input: 'question',
        output: 'answer',
        failures: [],
        expectationResults: [],
        location: 'tests/AgentEvals/ExampleTest.php:42',
    );

    $lines = [];
    $result->dump(function (string $line) use (&$lines): void {
        $lines[] = $line;
    });

    expect(implode("\n", $lines))->toContain('Location: tests/AgentEvals/ExampleTest.php:42');
});

it('dump supports json output format', function () {
    $result = new EvalResult(
        name: 'json-case',
        input: 'question',
        output: 'answer',
        failures: [],
        expectationResults: [['type' => 'contains', 'passed' => true, 'reason' => 'ok']],
        location: 'tests/AgentEvals/JsonTest.php:11',
    );

    $lines = [];
    $result->dump(function (string $line) use (&$lines): void {
        $lines[] = $line;
    }, 'json');

    expect($lines)->toHaveCount(1);

    $payload = json_decode($lines[0], true);

    expect($payload)->toBeArray();
    expect($payload['name'])->toBe('json-case');
    expect($payload['location'])->toBe('tests/AgentEvals/JsonTest.php:11');
    expect($payload['passed'])->toBeTrue();
});

it('dump rejects unsupported formats', function () {
    $result = new EvalResult(
        name: 'bad-format',
        input: 'question',
        output: 'answer',
        failures: [],
        expectationResults: [],
    );

    $result->dump(format: 'xml');
})->throws(RuntimeException::class, 'Unsupported eval output format "xml"');

it('exposes dd helper with never return type', function () {
    $method = new ReflectionMethod(EvalResult::class, 'dd');
    $returnType = $method->getReturnType();

    expect($returnType)->not->toBeNull();
    expect($returnType instanceof ReflectionNamedType)->toBeTrue();
    expect($returnType?->getName())->toBe('never');
});
