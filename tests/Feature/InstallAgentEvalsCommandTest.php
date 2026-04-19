<?php

declare(strict_types=1);

it('publishes config and creates eval directory', function () {
    $path = createInstallEvalDirectoryPath();
    $directory = base_path($path);

    if (is_dir($directory)) {
        deleteInstallEvalDirectory($directory);
    }

    $this->artisan('ai-evals:install', [
        '--path' => $path,
    ])->assertExitCode(0);

    expect(is_dir($directory))->toBeTrue();
    expect(is_file(config_path('laravel-ai-evaluation.php')))->toBeTrue();
});

function createInstallEvalDirectoryPath(): string
{
    static $registered = false;
    static $directories = [];

    if (! $registered) {
        register_shutdown_function(static function () use (&$directories): void {
            foreach ($directories as $directory) {
                deleteInstallEvalDirectory($directory);
            }
        });

        $registered = true;
    }

    $relativePath = 'tests/tmp-evals/'.uniqid('install-', true);
    $directories[] = base_path($relativePath);

    return $relativePath;
}

function deleteInstallEvalDirectory(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
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
