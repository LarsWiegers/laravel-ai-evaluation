<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeAgentEvalCommand extends Command
{
    public function __construct(
        protected Filesystem $files,
    ) {
        parent::__construct();
    }

    protected $signature = 'make:ai-evals
        {name : Eval name (for example: refund-policy)}
        {--type= : Eval style: pest or standalone}
        {--agent= : Agent class to use in generated template}
        {--path= : Relative output directory (defaults to tests/AgentEvals)}
        {--force : Overwrite an existing eval file}';

    protected $description = 'Create an AI eval file for Pest or standalone runs';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $type = $this->resolveType();

        if ($type === null) {
            $this->components->error('Invalid eval type. Supported values: pest, standalone.');

            return self::FAILURE;
        }

        $evalName = $this->resolveEvalName($name);

        if ($evalName === null) {
            $this->components->error('Eval name must contain at least one letter or number.');

            return self::FAILURE;
        }

        $fileStem = $this->resolveFileStem($name);

        if ($fileStem === null) {
            $this->components->error('Eval name must contain at least one letter or number.');

            return self::FAILURE;
        }

        $relativePath = (string) ($this->option('path') ?: config('laravel-ai-evaluation.standalone.path', 'tests/AgentEvals'));
        $relativePath = trim($relativePath, '/');
        $directory = base_path($relativePath);

        if (! $this->files->isDirectory($directory)) {
            $this->files->ensureDirectoryExists($directory);
        }

        if (! $this->files->isDirectory($directory)) {
            $this->components->error(sprintf('Unable to create directory [%s].', $relativePath));

            return self::FAILURE;
        }

        $fileName = $type === 'pest'
            ? sprintf('%sEvalTest.php', Str::studly($fileStem))
            : sprintf('%s.eval.php', $fileStem);

        $targetPath = $directory.'/'.$fileName;

        if ($this->files->isFile($targetPath) && ! (bool) $this->option('force')) {
            $this->components->error(sprintf('Eval file already exists: %s', $this->relativeToBasePath($targetPath)));

            return self::FAILURE;
        }

        $agentClass = $this->resolveAgentClass();

        $written = $this->files->put($targetPath, $this->buildTemplate($type, $evalName, $agentClass));

        if ($written === false) {
            $this->components->error(sprintf('Unable to write eval file [%s].', $this->relativeToBasePath($targetPath)));

            return self::FAILURE;
        }

        $this->components->info(sprintf('Created [%s].', $this->relativeToBasePath($targetPath)));

        if ($type === 'pest') {
            $this->components->info('Run with: vendor/bin/pest tests/AgentEvals');
        } else {
            $this->components->info('Run with: php artisan ai-evals:run');
        }

        return self::SUCCESS;
    }

    protected function resolveType(): ?string
    {
        $type = $this->option('type');

        if (! is_string($type) || trim($type) === '') {
            $type = $this->choice('Which eval file type do you want to create?', ['pest', 'standalone'], 0);
        }

        $type = strtolower(trim($type));

        return in_array($type, ['pest', 'standalone'], true) ? $type : null;
    }

    protected function buildTemplate(string $type, string $evalName, string $agentClass): string
    {
        $escapedEvalName = str_replace(['\\', "'"], ['\\\\', "\\'"], $evalName);

        if ($type === 'pest') {
            return <<<PHP
<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;

it('{$escapedEvalName}', function () {
    AIEval::agent({$agentClass}::class)
        ->input('What is your refund policy?')
        ->expectContains(['refund', '30 days'])
        ->run()
        ->assertPasses();
});
PHP;
        }

        return <<<PHP
<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

return static function (StandaloneEvalSuite \$suite): void {
    \$suite->eval('{$escapedEvalName}', static function () {
        return AIEval::agent({$agentClass}::class)
            ->input('What is your refund policy?')
            ->expectContains(['refund', '30 days'])
            ->run();
    });
};
PHP;
    }

    protected function relativeToBasePath(string $absolutePath): string
    {
        $basePath = rtrim(base_path(), '/').'/';

        if (str_starts_with($absolutePath, $basePath)) {
            return substr($absolutePath, strlen($basePath));
        }

        return $absolutePath;
    }

    protected function resolveAgentClass(): string
    {
        $agent = $this->option('agent');

        if (! is_string($agent) || trim($agent) === '') {
            if ($this->shouldPromptForAgent()) {
                $agent = $this->ask('Agent class to scaffold', 'App\\Ai\\Agents\\SupportAgent');
            }

            if (! is_string($agent) || trim($agent) === '') {
                return 'App\\Ai\\Agents\\SupportAgent';
            }
        }

        return ltrim(trim($agent), '\\');
    }

    protected function shouldPromptForAgent(): bool
    {
        if (! isset($this->input) || ! $this->input->isInteractive()) {
            return false;
        }

        if (! function_exists('app')) {
            return true;
        }

        try {
            return ! app()->runningUnitTests();
        } catch (\Throwable) {
            return true;
        }
    }

    protected function resolveEvalName(string $name): ?string
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            return null;
        }

        $sanitized = preg_replace('/\s+/', ' ', $trimmed);

        if (! is_string($sanitized) || preg_match('/[A-Za-z0-9]/', $sanitized) !== 1) {
            return null;
        }

        return $sanitized;
    }

    protected function resolveFileStem(string $name): ?string
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            return null;
        }

        $stem = preg_replace('/[^A-Za-z0-9_-]+/', '-', $trimmed);

        if (! is_string($stem)) {
            return null;
        }

        $stem = trim($stem, '-_');

        return $stem === '' ? null : $stem;
    }
}
