<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;

it('passes contains expectation using agent class string', function () {
    $result = AIEval::agent(FakeSupportAgent::class)
        ->name('refund-policy')
        ->input('What is your refund policy?')
        ->expectContains(['refund', '30 days'])
        ->run();

    expect($result->passed())->toBeTrue();
});

it('passes exact expectation using agent instance', function () {
    $result = AIEval::agent(new FakeHealthcheckAgent)
        ->name('healthcheck')
        ->input('Reply with exactly: OK')
        ->expectExact('OK')
        ->run();

    expect($result->passed())->toBeTrue();
});

it('throws when expectations fail', function () {
    AIEval::agent(new FakeHealthcheckAgent)
        ->name('failing-case')
        ->input('Reply with exactly: NOT_OK')
        ->expectExact('NOT_OK')
        ->run()
        ->assertPasses();
})->throws(PHPUnit\Framework\ExpectationFailedException::class);

it('uses pest test name when name is omitted', function () {
    $result = AIEval::agent(new FakeHealthcheckAgent)
        ->input('Reply with exactly: WRONG')
        ->expectExact('WRONG')
        ->run();

    expect(fn () => $result->assertPasses())
        ->toThrow(PHPUnit\Framework\ExpectationFailedException::class, "AI eval 'it uses pest test name when name is omitted' failed");
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
