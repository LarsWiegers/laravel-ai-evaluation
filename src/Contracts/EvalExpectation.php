<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Contracts;

use LaravelAIEvaluation\Evaluation\ExpectationResult;

interface EvalExpectation
{
    public function evaluate(string $input, string $output): ExpectationResult;
}
