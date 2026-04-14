<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Scoring;

class ExactScorer
{
    public function matches(string $actualOutput, string $expectedOutput): bool
    {
        return trim($actualOutput) === trim($expectedOutput);
    }
}
