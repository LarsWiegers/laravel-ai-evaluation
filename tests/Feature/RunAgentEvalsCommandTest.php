<?php

declare(strict_types=1);

use LaravelAIEvaluation\Console\StandaloneEvalRunner;

it('runs standalone eval command with default path', function () {
    $fakeRunner = new class extends StandaloneEvalRunner {
        public array $calls = [];

        public function run(string $path, ?string $filter, callable $output, string $format = 'text', ?string $outputPath = null): int
        {
            $this->calls[] = [
                'path' => $path,
                'filter' => $filter,
                'format' => $format,
                'output_path' => $outputPath,
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
    expect($fakeRunner->calls[0]['format'])->toBe('text');
    expect($fakeRunner->calls[0]['output_path'])->toBeNull();
});

it('passes path and filter options to standalone eval command', function () {
    $fakeRunner = new class extends StandaloneEvalRunner {
        public array $calls = [];

        public function run(string $path, ?string $filter, callable $output, string $format = 'text', ?string $outputPath = null): int
        {
            $this->calls[] = [
                'path' => $path,
                'filter' => $filter,
                'format' => $format,
                'output_path' => $outputPath,
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
    expect($fakeRunner->calls[0]['format'])->toBe('text');
    expect($fakeRunner->calls[0]['output_path'])->toBeNull();
});

it('passes format and output options to standalone eval command', function () {
    $fakeRunner = new class extends StandaloneEvalRunner {
        public array $calls = [];

        public function run(string $path, ?string $filter, callable $output, string $format = 'text', ?string $outputPath = null): int
        {
            $this->calls[] = [
                'path' => $path,
                'filter' => $filter,
                'format' => $format,
                'output_path' => $outputPath,
            ];

            return 0;
        }
    };

    app()->instance(StandaloneEvalRunner::class, $fakeRunner);

    $this->artisan('ai-evals:run', [
        '--format' => 'junit',
        '--output' => 'storage/ai-evals/junit.xml',
    ])
        ->assertExitCode(0);

    expect($fakeRunner->calls)->toHaveCount(1);
    expect($fakeRunner->calls[0]['format'])->toBe('junit');
    expect($fakeRunner->calls[0]['output_path'])->toBe('storage/ai-evals/junit.xml');
});

it('returns runner exit codes directly for non text formats', function () {
    $fakeRunner = new class extends StandaloneEvalRunner {
        public function run(string $path, ?string $filter, callable $output, string $format = 'text', ?string $outputPath = null): int
        {
            $output('{"failed":1}'.PHP_EOL);

            return 1;
        }
    };

    app()->instance(StandaloneEvalRunner::class, $fakeRunner);

    $this->artisan('ai-evals:run', ['--format' => 'json'])
        ->assertExitCode(1);
});

it('reports runner exceptions as command failures', function () {
    $fakeRunner = new class extends StandaloneEvalRunner {
        public function run(string $path, ?string $filter, callable $output, string $format = 'text', ?string $outputPath = null): int
        {
            throw new RuntimeException('Runner failed hard');
        }
    };

    app()->instance(StandaloneEvalRunner::class, $fakeRunner);

    $this->artisan('ai-evals:run')
        ->expectsOutputToContain('Runner failed hard')
        ->assertExitCode(1);
});

it('prints text success and failure status messages', function () {
    $passingRunner = new class extends StandaloneEvalRunner {
        public function run(string $path, ?string $filter, callable $output, string $format = 'text', ?string $outputPath = null): int
        {
            return 0;
        }
    };

    app()->instance(StandaloneEvalRunner::class, $passingRunner);

    $this->artisan('ai-evals:run')
        ->expectsOutputToContain('Agent evals passed.')
        ->assertExitCode(0);

    $failingRunner = new class extends StandaloneEvalRunner {
        public function run(string $path, ?string $filter, callable $output, string $format = 'text', ?string $outputPath = null): int
        {
            return 1;
        }
    };

    app()->instance(StandaloneEvalRunner::class, $failingRunner);

    $this->artisan('ai-evals:run')
        ->expectsOutputToContain('Agent evals failed.')
        ->assertExitCode(1);
});

it('prints text output path messages when writing reports', function () {
    $fakeRunner = new class extends StandaloneEvalRunner {
        public function run(string $path, ?string $filter, callable $output, string $format = 'text', ?string $outputPath = null): int
        {
            return 0;
        }
    };

    app()->instance(StandaloneEvalRunner::class, $fakeRunner);

    $this->artisan('ai-evals:run', ['--output' => 'storage/ai-evals/text.txt'])
        ->expectsOutputToContain('Wrote AI eval report to [storage/ai-evals/text.txt].')
        ->assertExitCode(0);
});
