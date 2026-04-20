<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation\Judge;

interface JudgeClient
{
    public function evaluate(
        string $input,
        string $actualOutput,
        string $criteria,
        ?string $reference = null,
        object|string|null $judge = null,
    ): JudgeVerdict;
}
