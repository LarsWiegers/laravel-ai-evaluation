<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\LaravelAIEvaluation\Console;

use RuntimeException;
use Symfony\Component\Process\Process;

class PestProcessRunner
{
    public function run(string $path, ?string $filter, callable $output): int
    {
        $binary = base_path('vendor/bin/pest');

        if (! file_exists($binary)) {
            throw new RuntimeException('Unable to find Pest binary at vendor/bin/pest.');
        }

        $command = [PHP_BINARY, $binary, $path];

        if ($filter !== null && $filter !== '') {
            $command[] = "--filter={$filter}";
        }

        $process = new Process($command, base_path());
        $process->setTimeout(null);
        $process->run(function (string $type, string $buffer) use ($output): void {
            $output($buffer);
        });

        return $process->getExitCode() ?? 1;
    }
}
