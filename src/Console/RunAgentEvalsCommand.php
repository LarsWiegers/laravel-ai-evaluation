<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\LaravelAIEvaluation\Console;

use Illuminate\Console\Command;
use Throwable;

class RunAgentEvalsCommand extends Command
{
    protected $signature = 'ai-evals:run
        {path? : Relative path to eval tests}
        {--filter= : Filter eval tests by name}';

    protected $description = 'Run AI agent eval tests without the full suite';

    public function handle(PestProcessRunner $runner): int
    {
        $path = (string) ($this->argument('path') ?: config('laravel-ai-evaluation.standalone.path', 'tests/AgentEvals'));
        $filter = $this->option('filter');
        $filter = is_string($filter) && $filter !== '' ? $filter : null;

        $this->components->info("Running agent evals in [{$path}]");

        try {
            $exitCode = $runner->run($path, $filter, function (string $buffer): void {
                $this->output->write($buffer);
            });
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($exitCode === self::SUCCESS) {
            $this->components->info('Agent evals passed.');

            return self::SUCCESS;
        }

        $this->components->error('Agent evals failed.');

        return $exitCode;
    }
}
