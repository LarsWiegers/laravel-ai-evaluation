<?php

declare(strict_types=1);

use LaravelAIEvaluation\Console\StandaloneEvalRunner;

it('runs standalone eval command with default path', function () {
    $fakeRunner = new class extends StandaloneEvalRunner {
        public array $calls = [];

        public function run(string $path, ?string $filter, callable $output): int
        {
            $this->calls[] = [
                'path' => $path,
                'filter' => $filter,
            ];

            return 0;
        }
    };

    app()->instance(StandaloneEvalRunner::class, $fakeRunner);

    $this->artisan('ai-evals:run')
        ->assertExitCode(0);

    expect($fakeRunner->calls)->toHaveCount(1);
    expect($fakeRunner->calls[0]['path'])->toBe('tests/AgentEvals');
    expect($fakeRunner->calls[0]['filter'])->toBeNull();
});

it('passes path and filter options to standalone eval command', function () {
    $fakeRunner = new class extends StandaloneEvalRunner {
        public array $calls = [];

        public function run(string $path, ?string $filter, callable $output): int
        {
            $this->calls[] = [
                'path' => $path,
                'filter' => $filter,
            ];

            return 0;
        }
    };

    app()->instance(StandaloneEvalRunner::class, $fakeRunner);

    $this->artisan('ai-evals:run', [
        'path' => 'custom/path',
        '--filter' => 'refund policy',
    ])
        ->assertExitCode(0);

    expect($fakeRunner->calls)->toHaveCount(1);
    expect($fakeRunner->calls[0]['path'])->toBe('custom/path');
    expect($fakeRunner->calls[0]['filter'])->toBe('refund policy');
});
