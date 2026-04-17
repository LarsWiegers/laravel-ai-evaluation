<?php

declare(strict_types=1);

namespace LaravelAIEvaluation;

use Illuminate\Support\Facades\Facade;

/**
 * @see \LaravelAIEvaluation\LaravelAIEvaluation
 */
class LaravelAIEvaluationFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-ai-evaluation';
    }
}
