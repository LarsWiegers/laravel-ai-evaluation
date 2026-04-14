<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\LaravelAIEvaluation\Evaluation;

use RuntimeException;

class EvalResult
{
    /**
     * @param  array<int, string>  $failures
     * @param  array<int, array<string, mixed>>  $expectationResults
     */
    public function __construct(
        protected string $caseId,
        protected string $input,
        protected string $output,
        protected array $failures,
        protected array $expectationResults = [],
    ) {
    }

    public function passed(): bool
    {
        return $this->failures === [];
    }

    /**
     * @return array<int, string>
     */
    public function failures(): array
    {
        return $this->failures;
    }

    public function output(): string
    {
        return $this->output;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function expectationResults(): array
    {
        return $this->expectationResults;
    }

    public function assertPasses(): self
    {
        if ($this->passed()) {
            return $this;
        }

        $message = sprintf(
            "AI eval '%s' failed.\nInput: %s\nOutput: %s\nFailures:\n- %s",
            $this->caseId,
            $this->input,
            $this->output,
            implode("\n- ", $this->failures),
        );

        throw new RuntimeException($message);
    }
}
