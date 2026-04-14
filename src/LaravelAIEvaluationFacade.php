<?php

namespace LaravelAIEvaluation\LaravelAIEvaluation;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Test\Test\Skeleton\SkeletonClass
 */
class LaravelAIEvaluationFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-ai-evaluation';
    }
}
