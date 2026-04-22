<?php

declare(strict_types=1);

it('creates a pest eval test file', function () {
    $path = createMakeEvalDirectory();

    $this->artisan('make:ai-evals', [
        'name' => 'refund-policy',
        '--type' => 'pest',
        '--path' => $path,
    ])->assertExitCode(0);

    $file = base_path($path.'/RefundPolicyEvalTest.php');

    expect(is_file($file))->toBeTrue();

    $content = (string) file_get_contents($file);

    expect($content)->toContain("it('refund-policy'");
    expect($content)->toContain('->assertPasses();');
});

it('creates a standalone eval file', function () {
    $path = createMakeEvalDirectory();

    $this->artisan('make:ai-evals', [
        'name' => 'refund-policy',
        '--type' => 'standalone',
        '--path' => $path,
    ])->assertExitCode(0);

    $file = base_path($path.'/refund-policy.eval.php');

    expect(is_file($file))->toBeTrue();

    $content = (string) file_get_contents($file);

    expect($content)->toContain('StandaloneEvalSuite');
    expect($content)->toContain("->eval('refund-policy'");
    expect($content)->not->toContain('->assertPasses();');
});

it('preserves eval name casing for standalone file name and suite label', function () {
    $path = createMakeEvalDirectory();

    $this->artisan('make:ai-evals', [
        'name' => 'FinancialAdvisorAgent',
        '--type' => 'standalone',
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
        '--type' => 'pest',
        '--path' => $path,
        '--agent' => 'App\\Ai\\Agents\\BillingAgent',
    ])->assertExitCode(0);

    $file = base_path($path.'/CustomAgentEvalTest.php');

    expect(is_file($file))->toBeTrue();

    $content = (string) file_get_contents($file);

    expect($content)->toContain('AIEval::agent(App\\Ai\\Agents\\BillingAgent::class)');
});

it('scaffolds the same custom agent class for pest and standalone templates', function () {
    $path = createMakeEvalDirectory();
    $agent = 'App\\Ai\\Agents\\BillingAgent';

    $this->artisan('make:ai-evals', [
        'name' => 'billing-pest',
        '--type' => 'pest',
        '--path' => $path,
        '--agent' => $agent,
    ])->assertExitCode(0);

    $this->artisan('make:ai-evals', [
        'name' => 'billing-standalone',
        '--type' => 'standalone',
        '--path' => $path,
        '--agent' => $agent,
    ])->assertExitCode(0);

    $pestFile = base_path($path.'/BillingPestEvalTest.php');
    $standaloneFile = base_path($path.'/billing-standalone.eval.php');

    expect(is_file($pestFile))->toBeTrue();
    expect(is_file($standaloneFile))->toBeTrue();

    $expectedAgentLine = 'AIEval::agent(App\\Ai\\Agents\\BillingAgent::class)';

    expect((string) file_get_contents($pestFile))->toContain($expectedAgentLine);
    expect((string) file_get_contents($standaloneFile))->toContain($expectedAgentLine);
});

it('fails for invalid eval type', function () {
    $path = createMakeEvalDirectory();

    $this->artisan('make:ai-evals', [
        'name' => 'refund-policy',
        '--type' => 'xml',
        '--path' => $path,
    ])->assertExitCode(1);
});

it('fails when file exists unless force is provided', function () {
    $path = createMakeEvalDirectory();

    $this->artisan('make:ai-evals', [
        'name' => 'refund-policy',
        '--type' => 'pest',
        '--path' => $path,
    ])->assertExitCode(0);

    $this->artisan('make:ai-evals', [
        'name' => 'refund-policy',
        '--type' => 'pest',
        '--path' => $path,
    ])->assertExitCode(1);

    $file = base_path($path.'/RefundPolicyEvalTest.php');
    file_put_contents($file, 'modified');

    $this->artisan('make:ai-evals', [
        'name' => 'refund-policy',
        '--type' => 'pest',
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
