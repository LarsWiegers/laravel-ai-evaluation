<?php

declare(strict_types=1);

use LaravelAIEvaluation\Console\StandaloneEvalRunner;

it('throws when no standalone eval files are found', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    expect(function () use ($runner, $path): void {
        $runner->run($path, null, static function (string $buffer): void {});
    })->toThrow(\RuntimeException::class, 'No standalone eval files (*.eval.php) found');
});

it('runs multiple eval definitions returned as standalone eval suite', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    file_put_contents(
        base_path("{$path}/suite.eval.php"),
        <<<'PHP'
<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

$suite = new StandaloneEvalSuite;

$suite->eval('alpha-case', static function () {
    return AIEval::agent(new class {
        public function prompt(string $prompt): string
        {
            return 'alpha';
        }
    })
        ->input('ignored')
        ->expectContains('alpha')
        ->run();
});

$suite->eval('beta-case', static function () {
    return AIEval::agent(new class {
        public function prompt(string $prompt): string
        {
            return 'beta';
        }
    })
        ->input('ignored')
        ->expectContains('beta')
        ->run();
});

return $suite;
PHP,
    );

    $lines = [];
    $exitCode = $runner->run($path, null, static function (string $buffer) use (&$lines): void {
        $lines[] = $buffer;
    });

    expect($exitCode)->toBe(0);
    expect(implode('', $lines))->toContain('PASS alpha-case');
    expect(implode('', $lines))->toContain('PASS beta-case');
});

it('fails when standalone eval file returns invalid data', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    file_put_contents(
        base_path("{$path}/invalid.eval.php"),
        <<<'PHP'
<?php

declare(strict_types=1);

return 123;
PHP,
    );

    expect(function () use ($runner, $path): void {
        $runner->run($path, null, static function (string $buffer): void {});
    })->toThrow(\RuntimeException::class, 'must return a callable or StandaloneEvalSuite instance');
});

it('returns failure when no standalone eval names match the filter', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    file_put_contents(
        base_path("{$path}/single.eval.php"),
        <<<'PHP'
<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

return static function (StandaloneEvalSuite $suite): void {
    $suite->eval('refund-policy', static function () {
        return AIEval::agent(new class {
            public function prompt(string $prompt): string
            {
                return 'Refunds are available within 30 days.';
            }
        })
            ->input('ignored')
            ->expectContains('refund')
            ->run();
    });
};
PHP,
    );

    $lines = [];
    $exitCode = $runner->run($path, 'billing', static function (string $buffer) use (&$lines): void {
        $lines[] = $buffer;
    });

    expect($exitCode)->toBe(1);
    expect(implode('', $lines))->toContain('No standalone eval names matched the provided filter.');
});

function createStandaloneEvalDirectory(): string
{
    static $registered = false;
    static $directories = [];

    if (! $registered) {
        register_shutdown_function(static function () use (&$directories): void {
            foreach ($directories as $directory) {
                deleteDirectory($directory);
            }
        });

        $registered = true;
    }

    $relativePath = 'tests/tmp-evals/'.uniqid('suite-', true);
    $absolutePath = base_path($relativePath);

    if (! is_dir($absolutePath)) {
        mkdir($absolutePath, 0777, true);
    }

    $directories[] = $absolutePath;

    return $relativePath;
}

function deleteDirectory(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $item) {
        $path = $item->getPathname();

        if ($item->isDir()) {
            rmdir($path);

            continue;
        }

        unlink($path);
    }

    rmdir($directory);
}
