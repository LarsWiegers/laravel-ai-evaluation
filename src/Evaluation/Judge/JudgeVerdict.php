<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation\Judge;

class JudgeVerdict
{
    public function __construct(
        public readonly float $score,
        public readonly string $reason,
        /**
         * @var array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int, cost?: float}
         */
        public readonly array $usage = [],
    ) {
    }
}
