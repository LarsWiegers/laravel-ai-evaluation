<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\LaravelAIEvaluation\Evaluation;

use PHPUnit\Framework\Assert;
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
        protected ?string $location = null,
        protected array $usage = [],
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

    public function location(): ?string
    {
        return $this->location;
    }

    /**
     * @return array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int, cost?: float}
     */
    public function usage(): array
    {
        return $this->usage;
    }

    public function assertPasses(): self
    {
        Assert::assertTrue(
            $this->passed(),
            sprintf(
                "AI eval '%s' failed.\nLocation: %s\nInput: %s\nOutput: %s\nFailures:\n- %s",
                $this->caseId,
                $this->location ?? 'unknown',
                $this->input,
                $this->output,
                implode("\n- ", $this->failures),
            ),
        );

        return $this;
    }

    public function dump(?callable $writer = null, string $format = 'text'): self
    {
        $writer = $writer ?? static function (string $line): void {
            fwrite(STDOUT, $line.PHP_EOL);
        };

        if (! in_array($format, ['text', 'json'], true)) {
            throw new RuntimeException(sprintf('Unsupported eval output format "%s". Supported formats: text, json.', $format));
        }

        if ($format === 'json') {
            $encoded = json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if (! is_string($encoded)) {
                throw new RuntimeException('Unable to encode eval result to JSON output.');
            }

            $writer($encoded);

            return $this;
        }

        $writer(sprintf("Eval case: %s", $this->caseId));
        $writer(sprintf('Location: %s', $this->location ?? 'unknown'));
        $writer(sprintf('Passed: %s', $this->passed() ? 'yes' : 'no'));
        $writer(sprintf('Input: %s', $this->input));
        $writer(sprintf('Output: %s', $this->output));

        foreach ($this->expectationResults as $index => $result) {
            $type = (string) ($result['type'] ?? 'unknown');
            $passed = (bool) ($result['passed'] ?? false);
            $line = sprintf('Expectation %d [%s] passed=%s', $index + 1, $type, $passed ? 'yes' : 'no');

            if ($type === 'judge' && isset($result['score'], $result['threshold'])) {
                $line .= sprintf(' score=%.3f threshold=%.3f', (float) $result['score'], (float) $result['threshold']);
            }

            if (isset($result['reason']) && is_string($result['reason']) && $result['reason'] !== '') {
                $line .= sprintf(' reason="%s"', $result['reason']);
            }

            $writer($line);
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'case' => $this->caseId,
            'location' => $this->location,
            'passed' => $this->passed(),
            'input' => $this->input,
            'output' => $this->output,
            'failures' => $this->failures,
            'expectation_results' => $this->expectationResults,
            'usage' => $this->usage,
        ];
    }

    public function dd(?callable $writer = null, string $format = 'text'): never
    {
        $this->dump($writer, $format);

        exit(1);
    }
}
