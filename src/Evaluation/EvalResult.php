<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\LaravelAIEvaluation\Evaluation;

use RuntimeException;

class EvalResult
{
    /**
     * @param  array<int, string>  $failures
     */
    public function __construct(
        protected string $caseId,
        protected string $input,
        protected string $output,
        protected array $failures,
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
