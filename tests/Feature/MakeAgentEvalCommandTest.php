<?php

declare(strict_types=1);

it('creates an eval file for the Artisan runner', function () {
    $path = createMakeEvalDirectory();

    $this->artisan('make:ai-evals', [
        'name' => 'refund-policy',
        '--path' => $path,
    ])->assertExitCode(0);

    $file = base_path($path.'/refund-policy.eval.php');

    expect(is_file($file))->toBeTrue();

    $content = (string) file_get_contents($file);

    expect($content)->toContain('StandaloneEvalSuite');
    expect($content)->toContain("->eval('refund-policy'");
    expect($content)->not->toContain('->assertPasses();');
});

it('preserves eval name casing for file name and suite label', function () {
    $path = createMakeEvalDirectory();

    $this->artisan('make:ai-evals', [
        'name' => 'FinancialAdvisorAgent',
        '--path' => $path,
    ])->assertExitCode(0);

    $file = base_path($path.'/FinancialAdvisorAgent.eval.php');

    expect(is_file($file))->toBeTrue();

    $content = (string) file_get_contents($file);

    expect($content)->toContain("->eval('FinancialAdvisorAgent'");
});

it('uses custom agent class in generated templates', function () {
    $path = createMakeEvalDirectory();

    $this->artisan('make:ai-evals', [
        'name' => 'custom-agent',
        '--path' => $path,
        '--agent' => 'App\\Ai\\Agents\\BillingAgent',
    ])->assertExitCode(0);

    $file = base_path($path.'/custom-agent.eval.php');

    expect(is_file($file))->toBeTrue();

    $content = (string) file_get_contents($file);

    expect($content)->toContain('AIEval::agent(App\\Ai\\Agents\\BillingAgent::class)');
});

it('scaffolds the custom agent class in the eval suite', function () {
    $path = createMakeEvalDirectory();
    $agent = 'App\\Ai\\Agents\\BillingAgent';

    $this->artisan('make:ai-evals', [
        'name' => 'billing',
        '--path' => $path,
        '--agent' => $agent,
    ])->assertExitCode(0);

    $file = base_path($path.'/billing.eval.php');

    expect(is_file($file))->toBeTrue();

    $expectedAgentLine = 'AIEval::agent(App\\Ai\\Agents\\BillingAgent::class)';

    expect((string) file_get_contents($file))->toContain($expectedAgentLine);
});

it('creates dataset backed eval files and sample datasets', function () {
    $path = createMakeEvalDirectory();

    $this->artisan('make:ai-evals', [
        'name' => 'refund-policy',
        '--path' => $path,
        '--dataset' => true,
    ])->assertExitCode(0);

    $evalFile = base_path($path.'/refund-policy.eval.php');
    $datasetFile = base_path($path.'/datasets/refund-policy.json');

    expect(is_file($evalFile))->toBeTrue();
    expect(is_file($datasetFile))->toBeTrue();
    expect((string) file_get_contents($evalFile))->toContain("->dataset('{$path}/datasets/refund-policy.json')");
    expect((string) file_get_contents($evalFile))->toContain("->expectContainsFrom('required_terms')");
    expect((string) file_get_contents($datasetFile))->toContain('refund inside window');
});

it('does not support selecting a test framework', function () {
    $path = createMakeEvalDirectory();

    expect(fn () => $this->artisan('make:ai-evals', [
        'name' => 'refund-policy',
        '--type' => 'pest',
        '--path' => $path,
    ]))->toThrow(\Symfony\Component\Console\Exception\InvalidOptionException::class, 'The "--type" option does not exist.');
});

it('fails when file exists unless force is provided', function () {
    $path = createMakeEvalDirectory();

    $this->artisan('make:ai-evals', [
        'name' => 'refund-policy',
        '--path' => $path,
    ])->assertExitCode(0);

    $this->artisan('make:ai-evals', [
        'name' => 'refund-policy',
        '--path' => $path,
    ])->assertExitCode(1);

    $file = base_path($path.'/refund-policy.eval.php');
    file_put_contents($file, 'modified');

    $this->artisan('make:ai-evals', [
        'name' => 'refund-policy',
        '--path' => $path,
        '--force' => true,
    ])->assertExitCode(0);

    expect((string) file_get_contents($file))->toContain('AIEval::agent');
});

function createMakeEvalDirectory(): string
{
    static $registered = false;
    static $directories = [];

    if (! $registered) {
        register_shutdown_function(static function () use (&$directories): void {
            foreach ($directories as $directory) {
                deleteMakeEvalDirectory($directory);
            }
        });

        $registered = true;
    }

    $relativePath = 'tests/tmp-evals/'.uniqid('make-', true);
    $absolutePath = base_path($relativePath);

    if (! is_dir($absolutePath)) {
        mkdir($absolutePath, 0777, true);
    }

    $directories[] = $absolutePath;

    return $relativePath;
}

function deleteMakeEvalDirectory(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $item) {
        $path = $item->getPathname();

        if ($item->isDir()) {
            rmdir($path);

            continue;
        }

        unlink($path);
    }

    rmdir($directory);
}
