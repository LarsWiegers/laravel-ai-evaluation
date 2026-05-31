<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation;

final class ExpectationResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly bool $passed,
        public readonly string $reason,
        public readonly ?float $score = null,
        public readonly array $metadata = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function pass(string $reason = 'Expectation passed.', ?float $score = null, array $metadata = []): self
    {
        return new self(true, $reason, $score, $metadata);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function fail(string $reason, ?float $score = null, array $metadata = []): self
    {
        return new self(false, $reason, $score, $metadata);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'passed' => $this->passed,
            'reason' => $this->reason,
        ];

        if ($this->score !== null) {
            $result['score'] = $this->score;
        }

        if ($this->metadata !== []) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }
}
