<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Scoring;

use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Judge\JudgeClient;

class JudgeScorer
{
    public function __construct(
        protected JudgeClient $judgeClient,
        protected float $defaultThreshold,
    ) {
    }

    /**
     * @return array{score: float, threshold: float, passed: bool, reason: string}
     */
    public function score(
        string $input,
        string $actualOutput,
        string $criteria,
        ?string $reference = null,
        ?float $threshold = null,
        object|string|null $judge = null,
    ): array {
        $verdict = $this->judgeClient->evaluate($input, $actualOutput, $criteria, $reference, $judge);
        $targetThreshold = $threshold ?? $this->defaultThreshold;

        return [
            'score' => $verdict->score,
            'threshold' => $targetThreshold,
            'passed' => $verdict->score >= $targetThreshold,
            'reason' => $verdict->reason,
        ];
    }
}
