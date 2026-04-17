<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Console;

use RuntimeException;
use Symfony\Component\Process\Process;

class PestProcessRunner
{
    public function run(string $path, ?string $filter, callable $output): int
    {
        $binary = $this->resolveBinaryPath();

        if (! file_exists($binary)) {
            throw new RuntimeException(sprintf('Unable to find Pest binary at %s.', $binary));
        }

        $command = $this->buildCommand($binary, $path);

        if ($filter !== null && $filter !== '') {
            $command[] = "--filter={$filter}";
        }

        $env = [];

        if (getenv('AI_EVAL_SUMMARY') === false) {
            $env['AI_EVAL_SUMMARY'] = '1';
        }

        if (getenv('AI_EVAL_SUMMARY_FORMAT') === false) {
            $env['AI_EVAL_SUMMARY_FORMAT'] = (string) config('laravel-ai-evaluation.format', 'text');
        }

        $process = new Process($command, base_path(), $env);
        $process->setTimeout(null);
        $process->run(function (string $type, string $buffer) use ($output): void {
            $output($buffer);
        });

        return $process->getExitCode() ?? 1;
    }

    protected function resolveBinaryPath(): string
    {
        $configured = (string) config('laravel-ai-evaluation.standalone.binary', 'vendor/bin/pest');
        $resolved = base_path($configured);

        if (DIRECTORY_SEPARATOR === '\\' && ! str_ends_with($resolved, '.bat')) {
            $windowsBinary = $resolved.'.bat';

            if (file_exists($windowsBinary)) {
                return $windowsBinary;
            }
        }

        return $resolved;
    }

    /**
     * @return array<int, string>
     */
    protected function buildCommand(string $binary, string $path): array
    {
        if (DIRECTORY_SEPARATOR === '\\' && str_ends_with($binary, '.bat')) {
            return [$binary, $path];
        }

        return [PHP_BINARY, $binary, $path];
    }
}
