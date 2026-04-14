<?php

declare(strict_types=1);

namespace Tests;

use LaravelAIEvaluation\LaravelAIEvaluation\LaravelAIEvaluationServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // additional setup
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelAIEvaluationServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // perform environment setup
    }
}
