<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Console\Reports;

class GithubStandaloneReportFormatter implements StandaloneReportFormatter
{
    public function format(StandaloneEvalRunReport $report): string
    {
        $output = '';

        foreach ($report->cases as $case) {
            if ($case->passed) {
                continue;
            }

            $location = $this->parseLocation($case->location);
            $properties = [];

            if ($location['file'] !== null) {
                $properties['file'] = $location['file'];
            }

            if ($location['line'] !== null) {
                $properties['line'] = (string) $location['line'];
            }

            $properties['title'] = sprintf('AI eval failed: %s', $case->name);

            $output .= sprintf(
                '::error %s::%s%s',
                $this->formatProperties($properties),
                $this->escapeData(implode("\n", $case->failures)),
                PHP_EOL,
            );
        }

        return $output;
    }

    /**
     * @param  array<string, string>  $properties
     */
    protected function formatProperties(array $properties): string
    {
        $formatted = [];

        foreach ($properties as $key => $value) {
            $formatted[] = sprintf('%s=%s', $key, $this->escapeProperty($value));
        }

        return implode(',', $formatted);
    }

    protected function escapeData(string $value): string
    {
        return str_replace(['%', "\r", "\n"], ['%25', '%0D', '%0A'], $value);
    }

    protected function escapeProperty(string $value): string
    {
        return str_replace(['%', "\r", "\n", ':', ','], ['%25', '%0D', '%0A', '%3A', '%2C'], $value);
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
