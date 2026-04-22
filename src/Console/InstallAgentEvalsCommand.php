<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelAIEvaluation\LaravelAIEvaluationServiceProvider;

class InstallAgentEvalsCommand extends Command
{
    protected $signature = 'ai-evals:install
        {--path= : Relative eval directory (defaults to tests/AgentEvals)}
        {--force : Overwrite published config if it already exists}';

    protected $description = 'Install Laravel AI Evaluation config and eval directory';

    public function __construct(
        protected Filesystem $files,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->components->info('Installing Laravel AI Evaluation...');

        $publishExitCode = $this->call('vendor:publish', [
            '--provider' => LaravelAIEvaluationServiceProvider::class,
            '--tag' => 'laravel-ai-evaluation-config',
            '--force' => (bool) $this->option('force'),
        ]);

        if ($publishExitCode !== self::SUCCESS) {
            $this->components->error('Unable to publish package configuration.');

            return self::FAILURE;
        }

        $relativePath = (string) ($this->option('path') ?: config('laravel-ai-evaluation.standalone.path', 'tests/AgentEvals'));
        $relativePath = trim($relativePath, '/');
        $directory = base_path($relativePath);

        $this->files->ensureDirectoryExists($directory);

        if (! $this->files->isDirectory($directory)) {
            $this->components->error(sprintf('Unable to create eval directory [%s].', $relativePath));

            return self::FAILURE;
        }

        $this->components->info(sprintf('Published config: config/laravel-ai-evaluation.php (tag: laravel-ai-evaluation-config)'));
        $this->components->info(sprintf('Ensured eval directory: %s', $relativePath));
        $this->newLine();
        $this->components->info('Next steps:');
        $this->line('  - php artisan make:ai-evals refund-policy --type=pest');
        $this->line('  - php artisan make:ai-evals refund-policy --type=standalone');
        $this->line('  - php artisan ai-evals:run');

        return self::SUCCESS;
    }
}
