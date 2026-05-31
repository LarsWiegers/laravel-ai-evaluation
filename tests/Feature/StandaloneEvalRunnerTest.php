<?php

declare(strict_types=1);

use LaravelAIEvaluation\Console\StandaloneEvalRunner;

it('throws when no standalone eval files are found', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    expect(function () use ($runner, $path): void {
        $runner->run($path, null, static function (string $buffer): void {});
    })->toThrow(\RuntimeException::class, 'No standalone eval files (*.eval.php) found');
});

it('runs multiple eval definitions returned as standalone eval suite', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    file_put_contents(
        base_path("{$path}/suite.eval.php"),
        <<<'PHP'
<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

$suite = new StandaloneEvalSuite;

$suite->eval('alpha-case', static function () {
    return AIEval::agent(new class {
        public function prompt(string $prompt): string
        {
            return 'alpha';
        }
    })
        ->input('ignored')
        ->expectContains('alpha')
        ->run();
});

$suite->eval('beta-case', static function () {
    return AIEval::agent(new class {
        public function prompt(string $prompt): string
        {
            return 'beta';
        }
    })
        ->input('ignored')
        ->expectContains('beta')
        ->run();
});

return $suite;
PHP,
    );

    $lines = [];
    $exitCode = $runner->run($path, null, static function (string $buffer) use (&$lines): void {
        $lines[] = $buffer;
    });

    expect($exitCode)->toBe(0);
    expect(implode('', $lines))->toContain('PASS alpha-case');
    expect(implode('', $lines))->toContain('PASS beta-case');
});

it('fails when standalone eval file returns invalid data', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    file_put_contents(
        base_path("{$path}/invalid.eval.php"),
        <<<'PHP'
<?php

declare(strict_types=1);

return 123;
PHP,
    );

    expect(function () use ($runner, $path): void {
        $runner->run($path, null, static function (string $buffer): void {});
    })->toThrow(\RuntimeException::class, 'must return a callable or StandaloneEvalSuite instance');
});

it('returns failure when no standalone eval names match the filter', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    file_put_contents(
        base_path("{$path}/single.eval.php"),
        <<<'PHP'
<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

return static function (StandaloneEvalSuite $suite): void {
    $suite->eval('refund-policy', static function () {
        return AIEval::agent(new class {
            public function prompt(string $prompt): string
            {
                return 'Refunds are available within 30 days.';
            }
        })
            ->input('ignored')
            ->expectContains('refund')
            ->run();
    });
};
PHP,
    );

    $lines = [];
    $exitCode = $runner->run($path, 'billing', static function (string $buffer) use (&$lines): void {
        $lines[] = $buffer;
    });

    expect($exitCode)->toBe(1);
    expect(implode('', $lines))->toContain('No standalone eval names matched the provided filter.');
});

it('prints failed contains output and colorized status labels', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    file_put_contents(
        base_path("{$path}/fails.eval.php"),
        <<<'PHP'
<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

return static function (StandaloneEvalSuite $suite): void {
    $suite->eval('contains-fail', static function () {
        return AIEval::agent(new class {
            public function prompt(string $prompt): string
            {
                return 'Agent says hello world';
            }
        })
            ->input('ignored')
            ->expectContains('refund')
            ->run();
    });
};
PHP,
    );

    $lines = [];
    $exitCode = $runner->run($path, null, static function (string $buffer) use (&$lines): void {
        $lines[] = $buffer;
    });

    $output = implode('', $lines);

    expect($exitCode)->toBe(1);
    expect($output)->toContain('<fg=red;options=bold>FAIL contains-fail</>');
    expect($output)->toContain('Returned output:');
    expect($output)->toContain('Agent says hello world');
});

it('prints standalone eval results as json', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    writeStandaloneReportFixture($path);

    $lines = [];
    $exitCode = $runner->run($path, null, static function (string $buffer) use (&$lines): void {
        $lines[] = $buffer;
    }, 'json');

    $payload = json_decode(implode('', $lines), true);

    expect($exitCode)->toBe(1);
    expect($payload['type'])->toBe('ai_eval_standalone_report');
    expect($payload['total'])->toBe(2);
    expect($payload['passed'])->toBe(1);
    expect($payload['failed'])->toBe(1);
    expect($payload['cases'][1]['location'])->toContain($path.'/reports.eval.php');
    expect($payload['cases'][0]['input'])->toBeNull();
    expect($payload['cases'][1]['failures'][0])->toContain('Missing required substring');
});

it('prints standalone eval results as junit xml', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    writeStandaloneReportFixture($path);

    $lines = [];
    $exitCode = $runner->run($path, null, static function (string $buffer) use (&$lines): void {
        $lines[] = $buffer;
    }, 'junit');

    $output = implode('', $lines);

    expect($exitCode)->toBe(1);
    expect($output)->toContain('<testsuites tests="2" failures="1" errors="0">');
    expect($output)->toContain('<testcase name="passing-case" classname="AI Evals"');
    expect($output)->toContain('file="'.$path.'/reports.eval.php"');
    expect($output)->toContain('<failure message="Missing required substring');
});

it('prints standalone eval failures as github annotations', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    writeStandaloneReportFixture($path);

    $lines = [];
    $exitCode = $runner->run($path, null, static function (string $buffer) use (&$lines): void {
        $lines[] = $buffer;
    }, 'github');

    $output = implode('', $lines);

    expect($exitCode)->toBe(1);
    expect($output)->toContain('::error file=tests/tmp-evals/');
    expect($output)->toContain('title=AI eval failed%3A failing-case');
    expect($output)->toContain('::Missing required substring');
});

it('writes standalone eval report to an output file', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();
    $outputPath = "{$path}/reports/results.json";

    writeStandaloneReportFixture($path);

    $lines = [];
    $exitCode = $runner->run($path, null, static function (string $buffer) use (&$lines): void {
        $lines[] = $buffer;
    }, 'json', $outputPath);

    $payload = json_decode((string) file_get_contents(base_path($outputPath)), true);

    expect($exitCode)->toBe(1);
    expect($lines)->toBe([]);
    expect($payload['total'])->toBe(2);
});

it('omits inputs and truncates and redacts report output', function () {
    config()->set('laravel-ai-evaluation.standalone.report.include_input', false);
    config()->set('laravel-ai-evaluation.standalone.report.include_output', true);
    config()->set('laravel-ai-evaluation.standalone.report.max_output_length', 12);
    config()->set('laravel-ai-evaluation.standalone.report.redact_patterns', ['/secret=[^\s]+/']);

    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    file_put_contents(
        base_path("{$path}/redacted.eval.php"),
        <<<'PHP'
<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

return static function (StandaloneEvalSuite $suite): void {
    $suite->eval('redacted-case', static function () {
        return AIEval::agent(new class {
            public function prompt(string $prompt): string
            {
                return 'secret=super-secret-token visible tail';
            }
        })
            ->input('private prompt text')
            ->expectContains('missing')
            ->run();
    });
};
PHP,
    );

    $lines = [];
    $runner->run($path, null, static function (string $buffer) use (&$lines): void {
        $lines[] = $buffer;
    }, 'json');

    $payload = json_decode(implode('', $lines), true);

    expect($payload['cases'][0]['input'])->toBeNull();
    expect($payload['cases'][0]['output'])->toContain('[REDACTED]');
    expect($payload['cases'][0]['output'])->toContain('[truncated]');
    expect($payload['cases'][0]['output'])->not->toContain('super-secret-token');
});

it('runs a single eval file and ignores non eval php files', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    file_put_contents(base_path("{$path}/ignored.php"), '<?php throw new RuntimeException("ignored");');
    file_put_contents(
        base_path("{$path}/single.eval.php"),
        <<<'PHP'
<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

return static function (StandaloneEvalSuite $suite): void {
    $suite->eval('single-file-case', static function () {
        return AIEval::agent(new class {
            public function prompt(string $prompt): string
            {
                return 'single';
            }
        })
            ->input('ignored')
            ->expectContains('single')
            ->run();
    });
};
PHP,
    );

    $lines = [];
    $exitCode = $runner->run("{$path}/single.eval.php", null, static function (string $buffer) use (&$lines): void {
        $lines[] = $buffer;
    });

    expect($exitCode)->toBe(0);
    expect(implode('', $lines))->toContain('PASS single-file-case');
});

it('runs nested eval files in sorted order', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    mkdir(base_path("{$path}/nested"), 0777, true);
    writeStandalonePassingEval(base_path("{$path}/z-last.eval.php"), 'z-last');
    writeStandalonePassingEval(base_path("{$path}/nested/a-first.eval.php"), 'a-first');

    $lines = [];
    $exitCode = $runner->run($path, null, static function (string $buffer) use (&$lines): void {
        $lines[] = $buffer;
    });

    $output = implode('', $lines);

    expect($exitCode)->toBe(0);
    expect(strpos($output, 'PASS a-first'))->toBeLessThan(strpos($output, 'PASS z-last'));
});

it('turns eval callback exceptions and invalid return values into report errors', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    file_put_contents(
        base_path("{$path}/errors.eval.php"),
        <<<'PHP'
<?php

declare(strict_types=1);

use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

return static function (StandaloneEvalSuite $suite): void {
    $suite->eval('throws-case', static function () {
        throw new RuntimeException('Callback exploded');
    });

    $suite->eval('invalid-result-case', static function () {
        return 'not an eval result';
    });
};
PHP,
    );

    $lines = [];
    $exitCode = $runner->run($path, null, static function (string $buffer) use (&$lines): void {
        $lines[] = $buffer;
    }, 'json');

    $payload = json_decode(implode('', $lines), true);

    expect($exitCode)->toBe(1);
    expect($payload['errors'])->toBe(2);
    expect($payload['cases'][0]['exception_class'])->toBe(RuntimeException::class);
    expect($payload['cases'][0]['failures'][0])->toBe('Callback exploded');
    expect($payload['cases'][1]['failures'][0])->toContain('must return an EvalResult');
});

it('rejects unsupported report formats', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    writeStandalonePassingEval(base_path("{$path}/case.eval.php"), 'passing-case');

    expect(function () use ($runner, $path): void {
        $runner->run($path, null, static function (string $buffer): void {}, 'xml');
    })->toThrow(RuntimeException::class, 'Unsupported standalone eval report format "xml"');
});

it('writes reports to absolute output paths', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();
    $outputPath = base_path("{$path}/absolute/results.json");

    writeStandalonePassingEval(base_path("{$path}/case.eval.php"), 'absolute-output-case');

    $lines = [];
    $exitCode = $runner->run($path, null, static function (string $buffer) use (&$lines): void {
        $lines[] = $buffer;
    }, 'json', $outputPath);

    $payload = json_decode((string) file_get_contents($outputPath), true);

    expect($exitCode)->toBe(0);
    expect($lines)->toBe([]);
    expect($payload['cases'][0]['name'])->toBe('absolute-output-case');
});

it('throws when report output cannot be written', function () {
    $runner = app(StandaloneEvalRunner::class);
    $path = createStandaloneEvalDirectory();

    writeStandalonePassingEval(base_path("{$path}/case.eval.php"), 'write-failure-case');
    file_put_contents(base_path("{$path}/not-a-directory"), 'file');

    expect(function () use ($runner, $path): void {
        $runner->run($path, null, static function (string $buffer): void {}, 'json', "{$path}/not-a-directory/results.json");
    })->toThrow(ErrorException::class, 'File exists');
});

function createStandaloneEvalDirectory(): string
{
    static $registered = false;
    static $directories = [];

    if (! $registered) {
        register_shutdown_function(static function () use (&$directories): void {
            foreach ($directories as $directory) {
                deleteDirectory($directory);
            }
        });

        $registered = true;
    }

    $relativePath = 'tests/tmp-evals/'.uniqid('suite-', true);
    $absolutePath = base_path($relativePath);

    if (! is_dir($absolutePath)) {
        mkdir($absolutePath, 0777, true);
    }

    $directories[] = $absolutePath;

    return $relativePath;
}

function writeStandaloneReportFixture(string $path): void
{
    file_put_contents(
        base_path("{$path}/reports.eval.php"),
        <<<'PHP'
<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

return static function (StandaloneEvalSuite $suite): void {
    $suite->eval('passing-case', static function () {
        return AIEval::agent(new class {
            public function prompt(string $prompt): string
            {
                return 'alpha';
            }
        })
            ->input('private passing prompt')
            ->expectContains('alpha')
            ->run();
    });

    $suite->eval('failing-case', static function () {
        return AIEval::agent(new class {
            public function prompt(string $prompt): string
            {
                return 'Agent says hello world';
            }
        })
            ->input('private failing prompt')
            ->expectContains('refund')
            ->run();
    });
};
PHP,
    );
}

function writeStandalonePassingEval(string $absolutePath, string $name): void
{
    $escapedName = str_replace(['\\', "'"], ['\\\\', "\\'"], $name);

    file_put_contents(
        $absolutePath,
        <<<PHP
<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

return static function (StandaloneEvalSuite \$suite): void {
    \$suite->eval('{$escapedName}', static function () {
        return AIEval::agent(new class {
            public function prompt(string \$prompt): string
            {
                return 'pass';
            }
        })
            ->input('ignored')
            ->expectContains('pass')
            ->run();
    });
};
PHP,
    );
}

function deleteDirectory(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST,
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
