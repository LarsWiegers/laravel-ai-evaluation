<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Console\Reports;

class StandaloneEvalRunReport
{
    /**
     * @param  array<int, StandaloneEvalCaseResult>  $cases
     */
    public function __construct(
        public readonly array $cases,
        public readonly bool $matchedFilter = true,
    ) {
    }

    public function total(): int
    {
        return count($this->cases);
    }

    public function passed(): int
    {
        return count(array_filter($this->cases, static fn (StandaloneEvalCaseResult $case): bool => $case->passed));
    }

    public function failed(): int
    {
        return $this->total() - $this->passed();
    }

    public function errors(): int
    {
        return count(array_filter($this->cases, static fn (StandaloneEvalCaseResult $case): bool => $case->errored()));
    }

    public function exitCode(): int
    {
        return $this->total() > 0 && $this->failed() === 0 ? 0 : 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'ai_eval_standalone_report',
            'total' => $this->total(),
            'passed' => $this->passed(),
            'failed' => $this->failed(),
            'errors' => $this->errors(),
            'matched_filter' => $this->matchedFilter,
            'cases' => array_map(
                static fn (StandaloneEvalCaseResult $case): array => $case->toArray(),
                $this->cases,
            ),
        ];
    }
}
