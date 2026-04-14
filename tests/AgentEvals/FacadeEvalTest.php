<?php

declare(strict_types=1);

use LaravelAIEvaluation\LaravelAIEvaluation\LaravelAIEvaluation;

it('passes contains expectation using agent class string', function () {
    $result = LaravelAIEvaluation::agent(FakeSupportAgent::class)
        ->case('refund-policy')
        ->input('What is your refund policy?')
        ->expectContains(['refund', '30 days'])
        ->run();

    expect($result->passed())->toBeTrue();
});

it('passes exact expectation using agent instance', function () {
    $result = LaravelAIEvaluation::agent(new FakeHealthcheckAgent)
        ->case('healthcheck')
        ->input('Reply with exactly: OK')
        ->expectExact('OK')
        ->run();

    expect($result->passed())->toBeTrue();
});

it('throws when expectations fail', function () {
    LaravelAIEvaluation::agent(new FakeHealthcheckAgent)
        ->case('failing-case')
        ->input('Reply with exactly: NOT_OK')
        ->expectExact('NOT_OK')
        ->run()
        ->assertPasses();
})->throws(RuntimeException::class);

it('uses pest test name when case is omitted', function () {
    $result = LaravelAIEvaluation::agent(new FakeHealthcheckAgent)
        ->input('Reply with exactly: WRONG')
        ->expectExact('WRONG')
        ->run();

    expect(fn () => $result->assertPasses())
        ->toThrow(RuntimeException::class, "AI eval 'it uses pest test name when case is omitted' failed");
});

class FakeSupportAgent
{
    public function prompt(string $prompt): string
    {
        return 'Our refund policy allows refunds within 30 days.';
    }
}

class FakeHealthcheckAgent
{
    public function prompt(string $prompt): string
    {
        return 'OK';
    }
}
