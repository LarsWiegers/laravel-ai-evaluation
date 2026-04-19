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

    protected $signature = 'ai-evals:make
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

        $slug = Str::slug($name);

        if ($slug === '') {
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
            ? sprintf('%sEvalTest.php', Str::studly($slug))
            : sprintf('%s.eval.php', $slug);

        $targetPath = $directory.'/'.$fileName;

        if ($this->files->isFile($targetPath) && ! (bool) $this->option('force')) {
            $this->components->error(sprintf('Eval file already exists: %s', $this->relativeToBasePath($targetPath)));

            return self::FAILURE;
        }

        $agentClass = $this->resolveAgentClass();

        $written = $this->files->put($targetPath, $this->buildTemplate($type, $slug, $agentClass));

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

    protected function buildTemplate(string $type, string $slug, string $agentClass): string
    {
        if ($type === 'pest') {
            return <<<PHP
<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;

it('{$slug}', function () {
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
    \$suite->eval('{$slug}', static function () {
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
            return 'App\\Ai\\Agents\\SupportAgent';
        }

        return ltrim(trim($agent), '\\');
    }
}
