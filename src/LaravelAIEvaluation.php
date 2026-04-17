<?php

declare(strict_types=1);

namespace LaravelAIEvaluation;

use LaravelAIEvaluation\Evaluation\EvalCaseBuilder;

class LaravelAIEvaluation
{
    public static function agent(object|string $agent): EvalCaseBuilder
    {
        return new EvalCaseBuilder($agent);
    }
}
