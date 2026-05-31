<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Console\Reports;

use LaravelAIEvaluation\Evaluation\EvalResult;

class StandaloneEvalCaseResult
{
    /**
     * @param  array<int, string>  $failures
     * @param  array<int, array<string, mixed>>  $expectationResults
     * @param  array<string, mixed>  $usage
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $passed,
        public readonly ?string $location = null,
        public readonly array $failures = [],
        public readonly ?string $input = null,
        public readonly ?string $output = null,
        public readonly array $expectationResults = [],
        public readonly array $usage = [],
        public readonly ?string $exceptionClass = null,
    ) {
    }

    public static function fromEvalResult(string $name, EvalResult $result, StandaloneReportSanitizer $sanitizer): self
    {
        $payload = $result->toArray();

        return new self(
            name: $name,
            passed: (bool) $payload['passed'],
            location: $sanitizer->location(is_string($payload['location'] ?? null) ? $payload['location'] : null),
            failures: array_map(
                static fn (string $failure): string => $sanitizer->failure($failure),
                $result->failures(),
            ),
            input: $sanitizer->input(is_string($payload['input'] ?? null) ? $payload['input'] : null),
            output: $sanitizer->output($result->output()),
            expectationResults: $sanitizer->value($result->expectationResults()),
            usage: $result->usage(),
        );
    }

    public static function fromException(string $name, string $location, \Throwable $exception, StandaloneReportSanitizer $sanitizer): self
    {
        return new self(
            name: $name,
            passed: false,
            location: $sanitizer->location($location),
            failures: [$sanitizer->failure($exception->getMessage())],
            exceptionClass: $exception::class,
        );
    }

    public static function failed(string $name, ?string $location, string $failure, StandaloneReportSanitizer $sanitizer): self
    {
        return new self(
            name: $name,
            passed: false,
            location: $sanitizer->location($location),
            failures: [$sanitizer->failure($failure)],
        );
    }

    public function errored(): bool
    {
        return $this->exceptionClass !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'location' => $this->location,
            'passed' => $this->passed,
            'failures' => $this->failures,
            'input' => $this->input,
            'output' => $this->output,
            'expectation_results' => $this->expectationResults,
            'usage' => $this->usage,
            'exception_class' => $this->exceptionClass,
        ];
    }
}
