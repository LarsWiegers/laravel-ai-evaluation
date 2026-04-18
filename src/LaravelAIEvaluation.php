<?php

declare(strict_types=1);

namespace LaravelAIEvaluation;

use LaravelAIEvaluation\Evaluation\EvalCaseBuilder;
use LaravelAIEvaluation\Evaluation\EvalRunner;

class LaravelAIEvaluation
{
    public static function agent(object|string $agent): EvalCaseBuilder
    {
        $runner = function_exists('app') ? app(EvalRunner::class) : new EvalRunner;

        return new EvalCaseBuilder($agent, $runner);
    }
}
