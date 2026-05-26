<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Console;

use LaravelAIEvaluation\Console\Reports\GithubStandaloneReportFormatter;
use LaravelAIEvaluation\Console\Reports\JsonStandaloneReportFormatter;
use LaravelAIEvaluation\Console\Reports\JunitStandaloneReportFormatter;
use LaravelAIEvaluation\Console\Reports\StandaloneEvalCaseResult;
use LaravelAIEvaluation\Console\Reports\StandaloneEvalRunReport;
use LaravelAIEvaluation\Console\Reports\StandaloneReportFormatter;
use LaravelAIEvaluation\Console\Reports\StandaloneReportSanitizer;
use LaravelAIEvaluation\Console\Reports\TextStandaloneReportFormatter;
use LaravelAIEvaluation\Evaluation\EvalResult;
use LaravelAIEvaluation\Standalone\StandaloneEvalContext;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

class StandaloneEvalRunner
{
    public function run(string $path, ?string $filter, callable $output, string $format = 'text', ?string $outputPath = null): int
    {
        $report = $this->buildReport($path, $filter);
        $formatted = $this->formatter($format)->format($report);

        if ($outputPath !== null && trim($outputPath) !== '') {
            $this->writeOutputFile($outputPath, $formatted);
        } else {
            $output($formatted);
        }

        return $report->exitCode();
    }

    public function buildReport(string $path, ?string $filter): StandaloneEvalRunReport
    {
        $files = $this->resolveEvalFiles($path);

        if ($files === []) {
            throw new RuntimeException(sprintf('No standalone eval files (*.eval.php) found at %s.', $path));
        }

        $cases = [];
        $sanitizer = new StandaloneReportSanitizer;

        foreach ($files as $file) {
            foreach ($this->loadDefinitions($file) as $definition) {
                $name = $definition['name'];

                if (! $this->matchesFilter($name, $filter)) {
                    continue;
                }

                try {
                    $result = StandaloneEvalContext::withName($name, function () use ($definition): mixed {
                        return ($definition['run'])();
                    });

                    if (! $result instanceof EvalResult) {
                        throw new RuntimeException(sprintf('Standalone eval "%s" must return an EvalResult.', $name));
                    }

                    $cases[] = StandaloneEvalCaseResult::fromEvalResult($name, $result, $sanitizer);
                } catch (Throwable $exception) {
                    $cases[] = StandaloneEvalCaseResult::fromException($name, sprintf('%s:1', $file), $exception, $sanitizer);
                }
            }
        }

        return new StandaloneEvalRunReport($cases, $cases !== []);
    }

    protected function formatter(string $format): StandaloneReportFormatter
    {
        return match ($format) {
            'text' => new TextStandaloneReportFormatter,
            'json' => new JsonStandaloneReportFormatter,
            'junit' => new JunitStandaloneReportFormatter,
            'github' => new GithubStandaloneReportFormatter,
            default => throw new RuntimeException(sprintf('Unsupported standalone eval report format "%s". Supported formats: text, json, junit, github.', $format)),
        };
    }

    protected function writeOutputFile(string $outputPath, string $contents): void
    {
        $path = $this->absoluteOutputPath($outputPath);
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create eval report output directory [%s].', $directory));
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException(sprintf('Unable to write eval report output file [%s].', $path));
        }
    }

    protected function absoluteOutputPath(string $outputPath): string
    {
        $isWindowsAbsolutePath = strlen($outputPath) >= 3
            && ctype_alpha($outputPath[0])
            && $outputPath[1] === ':'
            && in_array($outputPath[2], ['\\', '/'], true);

        if (str_starts_with($outputPath, '/') || $isWindowsAbsolutePath) {
            return $outputPath;
        }

        return base_path($outputPath);
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
