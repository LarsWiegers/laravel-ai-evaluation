<?php

declare(strict_types=1);

use LaravelAIEvaluation\Standalone\StandaloneEvalContext;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

it('rejects empty standalone eval names', function () {
    expect(function (): void {
        (new StandaloneEvalSuite)->eval('   ', static function (): void {});
    })->toThrow(InvalidArgumentException::class, 'Standalone eval name cannot be empty.');
});

it('stores standalone eval definitions in order', function () {
    $suite = new StandaloneEvalSuite;
    $first = static function (): string {
        return 'first';
    };
    $second = static function (): string {
        return 'second';
    };

    $returned = $suite->eval('first-case', $first)->eval('second-case', $second);

    expect($returned)->toBe($suite);
    expect($suite->definitions())->toBe([
        ['name' => 'first-case', 'run' => $first],
        ['name' => 'second-case', 'run' => $second],
    ]);
});

it('restores standalone eval context after nested callbacks and exceptions', function () {
    expect(StandaloneEvalContext::currentName())->toBeNull();

    StandaloneEvalContext::withName('outer', static function (): void {
        expect(StandaloneEvalContext::currentName())->toBe('outer');

        try {
            StandaloneEvalContext::withName('inner', static function (): void {
                expect(StandaloneEvalContext::currentName())->toBe('inner');

                throw new RuntimeException('stop');
            });
        } catch (RuntimeException) {
            expect(StandaloneEvalContext::currentName())->toBe('outer');
        }
    });

    expect(StandaloneEvalContext::currentName())->toBeNull();
});
