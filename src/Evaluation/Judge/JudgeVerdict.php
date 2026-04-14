<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Judge;

class JudgeVerdict
{
    public function __construct(
        public readonly float $score,
        public readonly string $reason,
    ) {
    }
}
