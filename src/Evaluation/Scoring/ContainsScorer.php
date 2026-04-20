<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation\Scoring;

class ContainsScorer
{
    /**
     * @param  array<int, string>  $needles
     * @return array<int, string>
     */
    public function missing(string $actualOutput, array $needles): array
    {
        $missing = [];

        foreach ($needles as $needle) {
            if (! str_contains($actualOutput, $needle)) {
                $missing[] = $needle;
            }
        }

        return $missing;
    }
}
