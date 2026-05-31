<?php

declare(strict_types=1);

namespace LaravelAIEvaluation;

use LaravelAIEvaluation\Evaluation\EvalCaseBuilder;
use LaravelAIEvaluation\Evaluation\EvalRunner;
use LaravelAIEvaluation\Evaluation\ToolCallRecorder;

final class AIEval
{
    public static function agent(object|string $agent): EvalCaseBuilder
    {
        $runner = function_exists('app') ? app(EvalRunner::class) : new EvalRunner;

        return new EvalCaseBuilder($agent, $runner);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public static function recordToolCall(string $name, array $arguments = [], ?string $id = null): void
    {
        ToolCallRecorder::record($name, $arguments, $id);
    }
}
