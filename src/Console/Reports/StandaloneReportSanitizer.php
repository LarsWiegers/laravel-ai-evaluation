<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Console\Reports;

class StandaloneReportSanitizer
{
    public function includeInput(): bool
    {
        return (bool) config('laravel-ai-evaluation.standalone.report.include_input', false);
    }

    public function includeOutput(): bool
    {
        return (bool) config('laravel-ai-evaluation.standalone.report.include_output', true);
    }

    public function input(?string $input): ?string
    {
        if (! $this->includeInput() || $input === null) {
            return null;
        }

        return $this->sanitizeText($input, $this->maxLength('max_input_length', 500));
    }

    public function output(?string $output): ?string
    {
        if (! $this->includeOutput() || $output === null) {
            return null;
        }

        return $this->sanitizeText($output, $this->maxLength('max_output_length', 2000));
    }

    public function failure(string $failure): string
    {
        return $this->sanitizeText($failure, $this->maxLength('max_failure_length', 1000));
    }

    public function location(?string $location): ?string
    {
        if ($location === null) {
            return null;
        }

        if (preg_match('/^(.*):(\d+)$/', $location, $matches) === 1) {
            return sprintf('%s:%d', $this->relativePath($matches[1]), (int) $matches[2]);
        }

        return $this->relativePath($location);
    }

    public function value(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->failure($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->value($item);
        }

        return $value;
    }

    protected function sanitizeText(string $text, int $maxLength): string
    {
        foreach ($this->redactPatterns() as $pattern) {
            $redacted = @preg_replace($pattern, '[REDACTED]', $text);

            if (is_string($redacted)) {
                $text = $redacted;
            }
        }

        if ($this->length($text) <= $maxLength) {
            return $text;
        }

        return $this->substring($text, 0, max(0, $maxLength)).'... [truncated]';
    }

    /**
     * @return array<int, string>
     */
    protected function redactPatterns(): array
    {
        $patterns = config('laravel-ai-evaluation.standalone.report.redact_patterns', []);

        return is_array($patterns) ? array_values(array_filter($patterns, 'is_string')) : [];
    }

    protected function maxLength(string $key, int $default): int
    {
        return max(0, (int) config("laravel-ai-evaluation.standalone.report.{$key}", $default));
    }

    protected function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    protected function substring(string $value, int $start, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, $start, $length) : substr($value, $start, $length);
    }

    protected function relativePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);

        if (function_exists('base_path')) {
            $base = rtrim(str_replace('\\', '/', base_path()), '/').'/';

            if (str_starts_with($normalized, $base)) {
                return substr($normalized, strlen($base));
            }
        }

        $cwd = getcwd();

        if (! is_string($cwd) || $cwd === '') {
            return $path;
        }

        $base = rtrim(str_replace('\\', '/', $cwd), '/').'/';

        return str_starts_with($normalized, $base) ? substr($normalized, strlen($base)) : $path;
    }
}
