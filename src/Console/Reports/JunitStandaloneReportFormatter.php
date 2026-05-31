<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Console\Reports;

class JunitStandaloneReportFormatter implements StandaloneReportFormatter
{
    public function format(StandaloneEvalRunReport $report): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= sprintf(
            '<testsuites tests="%d" failures="%d" errors="%d">%s',
            $report->total(),
            max(0, $report->failed() - $report->errors()),
            $report->errors(),
            PHP_EOL,
        );
        $xml .= sprintf(
            '  <testsuite name="AI Evals" tests="%d" failures="%d" errors="%d">%s',
            $report->total(),
            max(0, $report->failed() - $report->errors()),
            $report->errors(),
            PHP_EOL,
        );

        foreach ($report->cases as $case) {
            $location = $this->parseLocation($case->location);
            $attributes = sprintf('name="%s" classname="AI Evals"', $this->escape($case->name));

            if ($location['file'] !== null) {
                $attributes .= sprintf(' file="%s"', $this->escape($location['file']));
            }

            if ($location['line'] !== null) {
                $attributes .= sprintf(' line="%d"', $location['line']);
            }

            if ($case->passed) {
                $xml .= sprintf('    <testcase %s/>%s', $attributes, PHP_EOL);

                continue;
            }

            $tag = $case->errored() ? 'error' : 'failure';
            $message = implode("\n", $case->failures);

            $xml .= sprintf('    <testcase %s>%s', $attributes, PHP_EOL);
            $xml .= sprintf('      <%s message="%s">%s</%s>%s', $tag, $this->escape($message), $this->escape($message), $tag, PHP_EOL);
            $xml .= sprintf('    </testcase>%s', PHP_EOL);
        }

        $xml .= '  </testsuite>'.PHP_EOL;
        $xml .= '</testsuites>'.PHP_EOL;

        return $xml;
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * @return array{file: string|null, line: int|null}
     */
    protected function parseLocation(?string $location): array
    {
        if ($location === null) {
            return ['file' => null, 'line' => null];
        }

        if (preg_match('/^(.*):(\d+)$/', $location, $matches) === 1) {
            return ['file' => $this->relativePath($matches[1]), 'line' => (int) $matches[2]];
        }

        return ['file' => $this->relativePath($location), 'line' => null];
    }

    protected function relativePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);

        if (! function_exists('base_path')) {
            return $this->relativeToCurrentWorkingDirectory($normalized, $path);
        }

        $base = rtrim(str_replace('\\', '/', base_path()), '/').'/';

        if (str_starts_with($normalized, $base)) {
            return substr($normalized, strlen($base));
        }

        return $this->relativeToCurrentWorkingDirectory($normalized, $path);
    }

    protected function relativeToCurrentWorkingDirectory(string $normalized, string $original): string
    {
        $cwd = getcwd();

        if (! is_string($cwd) || $cwd === '') {
            return $original;
        }

        $base = rtrim(str_replace('\\', '/', $cwd), '/').'/';

        return str_starts_with($normalized, $base) ? substr($normalized, strlen($base)) : $original;
    }
}
