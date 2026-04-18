<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Console;

use LaravelAIEvaluation\Evaluation\EvalResult;
use LaravelAIEvaluation\Standalone\StandaloneEvalContext;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

class StandaloneEvalRunner
{
    public function run(string $path, ?string $filter, callable $output): int
    {
        $files = $this->resolveEvalFiles($path);

        if ($files === []) {
            throw new RuntimeException(sprintf('No standalone eval files (*.eval.php) found at %s.', $path));
        }

        $summary = [
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
        ];

        foreach ($files as $file) {
            foreach ($this->loadDefinitions($file) as $definition) {
                $name = $definition['name'];

                if (! $this->matchesFilter($name, $filter)) {
                    continue;
                }

                $summary['total']++;

                try {
                    $result = StandaloneEvalContext::withName($name, function () use ($definition): mixed {
                        return ($definition['run'])();
                    });

                    if (! $result instanceof EvalResult) {
                        throw new RuntimeException(sprintf('Standalone eval "%s" must return an EvalResult.', $name));
                    }

                    if ($result->passed()) {
                        $summary['passed']++;
                        $output(sprintf("PASS %s\n", $name));

                        continue;
                    }

                    $summary['failed']++;
                    $output(sprintf("FAIL %s\n", $name));

                    foreach ($result->failures() as $failure) {
                        $output(sprintf("  - %s\n", $failure));
                    }
                } catch (Throwable $exception) {
                    $summary['failed']++;
                    $output(sprintf("ERROR %s\n", $name));
                    $output(sprintf("  - %s\n", $exception->getMessage()));
                }
            }
        }

        if ($summary['total'] === 0) {
            $output("No standalone eval names matched the provided filter.\n");

            return 1;
        }

        $output(sprintf("\nStandalone eval summary: total=%d passed=%d failed=%d\n", $summary['total'], $summary['passed'], $summary['failed']));

        return $summary['failed'] === 0 ? 0 : 1;
    }

    /**
     * @return array<int, string>
     */
    protected function resolveEvalFiles(string $path): array
    {
        $resolved = base_path($path);

        if (is_file($resolved)) {
            return str_ends_with($resolved, '.eval.php') ? [$resolved] : [];
        }

        if (! is_dir($resolved)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resolved));

        foreach ($iterator as $item) {
            if (! $item->isFile()) {
                continue;
            }

            $filePath = $item->getPathname();

            if (! str_ends_with($filePath, '.eval.php')) {
                continue;
            }

            $files[] = $filePath;
        }

        sort($files);

        return $files;
    }

    /**
     * @return array<int, array{name: string, run: callable}>
     */
    protected function loadDefinitions(string $file): array
    {
        $loaded = require $file;

        if ($loaded instanceof StandaloneEvalSuite) {
            return $loaded->definitions();
        }

        if (! is_callable($loaded)) {
            throw new RuntimeException(sprintf('Standalone eval file %s must return a callable or StandaloneEvalSuite instance.', $file));
        }

        $suite = new StandaloneEvalSuite;
        $loaded($suite);

        return $suite->definitions();
    }

    protected function matchesFilter(string $name, ?string $filter): bool
    {
        if ($filter === null || trim($filter) === '') {
            return true;
        }

        return str_contains(strtolower($name), strtolower($filter));
    }
}
