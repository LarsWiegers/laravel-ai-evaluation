<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Console;

use Illuminate\Console\Command;
use Throwable;

class RunAgentEvalsCommand extends Command
{
    protected $signature = 'ai-evals:run
        {path? : Relative path to standalone eval files}
        {--filter= : Filter eval cases by name}
        {--format=text : Output format: text, json, junit, github}
        {--output= : Write the formatted report to this path}';

    protected $description = 'Run standalone AI evals without a test framework';

    public function handle(StandaloneEvalRunner $runner): int
    {
        $path = (string) ($this->argument('path') ?: config('laravel-ai-evaluation.standalone.path', 'tests/AgentEvals'));
        $filter = $this->option('filter');
        $filter = is_string($filter) && $filter !== '' ? $filter : null;
        $format = (string) $this->option('format');
        $outputPath = $this->option('output');
        $outputPath = is_string($outputPath) && $outputPath !== '' ? $outputPath : null;

        if ($format === 'text') {
            $this->components->info("Running agent evals in [{$path}]");
        }

        try {
            $exitCode = $runner->run(
                path: $path,
                filter: $filter,
                output: function (string $buffer): void {
                    $this->output->write($buffer);
                },
                format: $format,
                outputPath: $outputPath,
            );
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($outputPath !== null && $format === 'text') {
            $this->components->info("Wrote AI eval report to [{$outputPath}].");
        }

        if ($format !== 'text') {
            return $exitCode;
        }

        if ($exitCode === self::SUCCESS) {
            $this->components->info('Agent evals passed.');

            return self::SUCCESS;
        }

        $this->components->error('Agent evals failed.');

        return $exitCode;
    }
}
