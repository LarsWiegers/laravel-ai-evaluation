<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation;

use RuntimeException;

class DatasetEvalResult
{
    /**
     * @param  array<int, EvalResult>  $results
     */
    public function __construct(
        protected string $name,
        protected string $datasetPath,
        protected array $results,
    ) {
    }

    /**
     * @return array<int, EvalResult>
     */
    public function results(): array
    {
        return $this->results;
    }

    public function passed(): bool
    {
        return $this->results !== [] && $this->failures() === [];
    }

    /**
     * @return array<int, string>
     */
    public function failures(): array
    {
        $failures = [];

        foreach ($this->results as $result) {
            foreach ($result->failures() as $failure) {
                $failures[] = $failure;
            }
        }

        return $failures;
    }

    public function assertPasses(): self
    {
        if ($this->passed()) {
            return $this;
        }

        $message = sprintf(
            "AI eval dataset '%s' failed.\nDataset: %s\nFailures:\n- %s",
            $this->name,
            $this->datasetPath,
            implode("\n- ", $this->failures()),
        );

        if (class_exists('PHPUnit\\Framework\\ExpectationFailedException')) {
            throw new \PHPUnit\Framework\ExpectationFailedException($message);
        }

        throw new RuntimeException($message);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'ai_eval_dataset_result',
            'name' => $this->name,
            'dataset_path' => $this->datasetPath,
            'total' => count($this->results),
            'passed' => count(array_filter($this->results, static fn (EvalResult $result): bool => $result->passed())),
            'failed' => count(array_filter($this->results, static fn (EvalResult $result): bool => ! $result->passed())),
            'results' => array_map(
                static fn (EvalResult $result): array => $result->toArray(),
                $this->results,
            ),
        ];
    }
}
