<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation;

use LaravelAIEvaluation\Evaluation\Judge\PromptJudgeClient;
use LaravelAIEvaluation\Evaluation\Scoring\ContainsScorer;
use LaravelAIEvaluation\Evaluation\Scoring\ExactScorer;
use LaravelAIEvaluation\Evaluation\Scoring\JudgeScorer;
use LaravelAIEvaluation\Evaluation\Support\PromptingTargetResolver;
use LaravelAIEvaluation\Evaluation\Support\ResponseNormalizer;
use LaravelAIEvaluation\Standalone\StandaloneEvalContext;
use RuntimeException;
use Throwable;

class EvalRunner
{
    public function __construct(
        protected ContainsScorer $containsScorer = new ContainsScorer,
        protected ExactScorer $exactScorer = new ExactScorer,
        protected ?JudgeScorer $judgeScorer = null,
        protected ?EvalRunSummary $runSummary = null,
        protected ?ResponseNormalizer $responseNormalizer = null,
        protected ?PromptingTargetResolver $targetResolver = null,
        protected ?int $retries = null,
        protected ?int $retrySleepMs = null,
    ) {
        $this->judgeScorer = $this->judgeScorer ?? new JudgeScorer(
            new PromptJudgeClient,
            (float) config('laravel-ai-evaluation.judge.threshold', 0.7),
        );

        $this->retries = $this->retries ?? max(0, (int) config('laravel-ai-evaluation.retries', 0));
        $this->retrySleepMs = $this->retrySleepMs ?? max(0, (int) config('laravel-ai-evaluation.retry_sleep_ms', 0));
        $this->runSummary = $this->runSummary ?? (function_exists('app') ? app(EvalRunSummary::class) : new EvalRunSummary);
        $this->responseNormalizer = $this->responseNormalizer ?? new ResponseNormalizer;
        $this->targetResolver = $this->targetResolver ?? new PromptingTargetResolver;
    }

    /**
     * @param  array<int, string>  $contains
     * @param  array<int, array{criteria: string, reference: string|null, threshold: float|null, judge: object|string|null}>  $judgeExpectations
     * @param  array<int, array<string, mixed>>  $deterministicExpectations
     */
    public function run(
        object|string $agent,
        ?string $name,
        string $input,
        array $contains = [],
        ?string $exact = null,
        array $judgeExpectations = [],
        ?string $location = null,
        array $deterministicExpectations = [],
    ): EvalResult {
        $name = $this->resolveName($name);

        if ($contains === [] && $exact === null && $judgeExpectations === [] && $deterministicExpectations === []) {
            throw new RuntimeException("AI eval '{$name}' must define at least one expectation.");
        }

        $resolvedAgent = $this->targetResolver->resolve($agent, 'agent', $name);

        $response = $this->promptAgent($resolvedAgent, $input, $name);

        $usage = $this->responseNormalizer->extractUsage($response);
        $output = $this->responseNormalizer->stringifyResponse($response, 'AI agent');

        $failures = [];
        $expectationResults = [];

        if ($contains !== []) {
            $missing = $this->containsScorer->missing($output, $contains);
            $passed = $missing === [];

            $expectationResults[] = [
                'type' => 'contains',
                'passed' => $passed,
                'reason' => $passed
                    ? 'All expected substrings are present.'
                    : sprintf('Missing required substring(s): %s', implode(', ', $missing)),
            ];

            if (! $passed) {
                $failures[] = $expectationResults[array_key_last($expectationResults)]['reason'];
            }
        }

        if ($exact !== null) {
            $passed = $this->exactScorer->matches($output, $exact);

            $expectationResults[] = [
                'type' => 'exact',
                'passed' => $passed,
                'reason' => $passed
                    ? 'Output exactly matches expected value.'
                    : sprintf('Expected exact output "%s" but received "%s"', trim($exact), trim($output)),
            ];

            if (! $passed) {
                $failures[] = $expectationResults[array_key_last($expectationResults)]['reason'];
            }
        }

        foreach ($deterministicExpectations as $expectation) {
            $result = $this->scoreDeterministicExpectation($output, $expectation);

            $expectationResults[] = $result;

            if (! $result['passed']) {
                $failures[] = $result['reason'];
            }
        }

        foreach ($judgeExpectations as $judgeExpectation) {
            $result = $this->scoreJudgeExpectation(
                input: $input,
                output: $output,
                judgeExpectation: $judgeExpectation,
            );

            $expectationResults[] = [
                'type' => 'judge',
                'passed' => $result['passed'],
                'score' => $result['score'],
                'threshold' => $result['threshold'],
                'reason' => $result['reason'],
                'criteria' => $judgeExpectation['criteria'],
                'reference' => $judgeExpectation['reference'],
                'usage' => $result['usage'],
            ];

            $usage = $this->mergeUsage($usage, $result['usage']);

            if (! $result['passed']) {
                $failures[] = sprintf(
                    'Judge expectation failed (score %.3f < %.3f): %s',
                    $result['score'],
                    $result['threshold'],
                    $result['reason'],
                );
            }
        }

        $result = new EvalResult($name, $input, $output, $failures, $expectationResults, $location, $usage);

        $this->runSummary->record($result);

        if ((bool) config('laravel-ai-evaluation.verbose', false)) {
            $result->dump(format: (string) config('laravel-ai-evaluation.format', 'text'));
        }

        return $result;
    }

    protected function resolveName(?string $name): string
    {
        if (is_string($name) && trim($name) !== '') {
            return $name;
        }

        $standaloneName = StandaloneEvalContext::currentName();

        if ($standaloneName !== null) {
            return $standaloneName;
        }

        return 'unnamed-eval';
    }

    /**
     * @param  array<string, mixed>  $expectation
     * @return array<string, mixed>
     */
    protected function scoreDeterministicExpectation(string $output, array $expectation): array
    {
        return match ($expectation['type'] ?? null) {
            'regex' => $this->scoreRegexExpectation($output, (string) ($expectation['pattern'] ?? '')),
            'not_contains' => $this->scoreNotContainsExpectation($output, $expectation['values'] ?? []),
            'json' => $this->scoreJsonExpectation($output),
            'json_path' => $this->scoreJsonPathExpectation(
                output: $output,
                path: (string) ($expectation['path'] ?? ''),
                hasExpected: (bool) ($expectation['has_expected'] ?? false),
                expected: $expectation['expected'] ?? null,
            ),
            'length' => $this->scoreLengthExpectation(
                output: $output,
                min: $expectation['min'] ?? null,
                max: $expectation['max'] ?? null,
            ),
            'starts_with' => $this->scoreStartsWithExpectation($output, (string) ($expectation['value'] ?? '')),
            'ends_with' => $this->scoreEndsWithExpectation($output, (string) ($expectation['value'] ?? '')),
            default => [
                'type' => (string) ($expectation['type'] ?? 'unknown'),
                'passed' => false,
                'reason' => sprintf('Unknown deterministic expectation type "%s".', (string) ($expectation['type'] ?? 'unknown')),
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function scoreRegexExpectation(string $output, string $pattern): array
    {
        $matches = @preg_match($pattern, $output);
        $passed = $matches === 1;

        return [
            'type' => 'regex',
            'passed' => $passed,
            'pattern' => $pattern,
            'reason' => match ($matches) {
                1 => sprintf('Output matches regex pattern %s.', $pattern),
                0 => sprintf('Output did not match regex pattern %s.', $pattern),
                default => sprintf('Invalid regex pattern %s.', $pattern),
            },
        ];
    }

    /**
     * @param  mixed  $values
     * @return array<string, mixed>
     */
    protected function scoreNotContainsExpectation(string $output, mixed $values): array
    {
        $forbidden = [];

        foreach (is_array($values) ? $values : [$values] as $value) {
            $value = (string) $value;

            if (str_contains($output, $value)) {
                $forbidden[] = $value;
            }
        }

        $passed = $forbidden === [];

        return [
            'type' => 'not_contains',
            'passed' => $passed,
            'values' => is_array($values) ? array_values($values) : [$values],
            'reason' => $passed
                ? 'No forbidden substrings are present.'
                : sprintf('Output contained forbidden substring(s): %s', implode(', ', $forbidden)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function scoreJsonExpectation(string $output): array
    {
        json_decode($output, true);
        $passed = json_last_error() === JSON_ERROR_NONE;

        return [
            'type' => 'json',
            'passed' => $passed,
            'reason' => $passed
                ? 'Output is valid JSON.'
                : sprintf('Output is not valid JSON: %s', json_last_error_msg()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function scoreJsonPathExpectation(string $output, string $path, bool $hasExpected, mixed $expected): array
    {
        $decoded = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'type' => 'json_path',
                'passed' => false,
                'path' => $path,
                'expected' => $hasExpected ? $expected : null,
                'has_expected' => $hasExpected,
                'reason' => sprintf('Output is not valid JSON, so JSON path "%s" could not be checked: %s', $path, json_last_error_msg()),
            ];
        }

        $resolved = $this->resolveJsonPath($decoded, $path);

        if (! $resolved['exists']) {
            return [
                'type' => 'json_path',
                'passed' => false,
                'path' => $path,
                'expected' => $hasExpected ? $expected : null,
                'has_expected' => $hasExpected,
                'reason' => sprintf('JSON path "%s" was not found.', $path),
            ];
        }

        if (! $hasExpected) {
            return [
                'type' => 'json_path',
                'passed' => true,
                'path' => $path,
                'has_expected' => false,
                'actual' => $resolved['value'],
                'reason' => sprintf('JSON path "%s" exists.', $path),
            ];
        }

        $passed = $resolved['value'] === $expected;

        return [
            'type' => 'json_path',
            'passed' => $passed,
            'path' => $path,
            'expected' => $expected,
            'has_expected' => true,
            'actual' => $resolved['value'],
            'reason' => $passed
                ? sprintf('JSON path "%s" matched expected value %s.', $path, $this->formatValue($expected))
                : sprintf('Expected JSON path "%s" to equal %s, but received %s.', $path, $this->formatValue($expected), $this->formatValue($resolved['value'])),
        ];
    }

    /**
     * @return array{exists: bool, value: mixed}
     */
    protected function resolveJsonPath(mixed $data, string $path): array
    {
        $normalizedPath = trim($path);

        if ($normalizedPath === '' || $normalizedPath === '$') {
            return ['exists' => true, 'value' => $data];
        }

        if (str_starts_with($normalizedPath, '$.')) {
            $normalizedPath = substr($normalizedPath, 2);
        }

        foreach (explode('.', $normalizedPath) as $segment) {
            if (! is_array($data)) {
                return ['exists' => false, 'value' => null];
            }

            $key = ctype_digit($segment) ? (int) $segment : $segment;

            if (! array_key_exists($key, $data)) {
                return ['exists' => false, 'value' => null];
            }

            $data = $data[$key];
        }

        return ['exists' => true, 'value' => $data];
    }

    /**
     * @return array<string, mixed>
     */
    protected function scoreLengthExpectation(string $output, mixed $min, mixed $max): array
    {
        $min = is_int($min) ? $min : null;
        $max = is_int($max) ? $max : null;
        $length = function_exists('mb_strlen') ? mb_strlen($output) : strlen($output);
        $passed = ($min === null || $length >= $min) && ($max === null || $length <= $max);

        return [
            'type' => 'length',
            'passed' => $passed,
            'min' => $min,
            'max' => $max,
            'actual_length' => $length,
            'reason' => $passed
                ? sprintf('Output length %d is within expected bounds.', $length)
                : sprintf('Expected output length %s, but received %d characters.', $this->formatLengthBounds($min, $max), $length),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function scoreStartsWithExpectation(string $output, string $value): array
    {
        $passed = str_starts_with($output, $value);

        return [
            'type' => 'starts_with',
            'passed' => $passed,
            'value' => $value,
            'reason' => $passed
                ? sprintf('Output starts with "%s".', $value)
                : sprintf('Expected output to start with "%s".', $value),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function scoreEndsWithExpectation(string $output, string $value): array
    {
        $passed = str_ends_with($output, $value);

        return [
            'type' => 'ends_with',
            'passed' => $passed,
            'value' => $value,
            'reason' => $passed
                ? sprintf('Output ends with "%s".', $value)
                : sprintf('Expected output to end with "%s".', $value),
        ];
    }

    protected function formatValue(mixed $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($encoded) ? $encoded : get_debug_type($value);
    }

    protected function formatLengthBounds(?int $min, ?int $max): string
    {
        if ($min !== null && $max !== null) {
            return sprintf('between %d and %d characters', $min, $max);
        }

        if ($min !== null) {
            return sprintf('to be at least %d characters', $min);
        }

        return sprintf('to be at most %d characters', $max);
    }

    protected function isAuthenticationFailure(Throwable $exception): bool
    {
        if ($exception->getCode() === 401) {
            return true;
        }

        return str_contains(strtolower($exception->getMessage()), '401');
    }

    protected function promptAgent(object $agent, string $input, string $name): mixed
    {
        $attempt = 0;

        while (true) {
            try {
                return $agent->prompt($input);
            } catch (Throwable $exception) {
                if ($this->isAuthenticationFailure($exception)) {
                    throw new RuntimeException(
                        "AI eval '{$name}' failed: Authentication error. Check your AI provider API key is configured.",
                        0,
                        $exception,
                    );
                }

                if ($attempt >= $this->retries || ! $this->shouldRetry($exception)) {
                    throw $exception;
                }

                $attempt++;
                $this->sleepBeforeRetry();
            }
        }
    }

    /**
     * @param  array{criteria: string, reference: string|null, threshold: float|null, judge: object|string|null}  $judgeExpectation
     * @return array{score: float, threshold: float, passed: bool, reason: string, usage: array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int, cost?: float}}
     */
    protected function scoreJudgeExpectation(string $input, string $output, array $judgeExpectation): array
    {
        $attempt = 0;

        while (true) {
            try {
                return $this->judgeScorer->score(
                    input: $input,
                    actualOutput: $output,
                    criteria: $judgeExpectation['criteria'],
                    reference: $judgeExpectation['reference'],
                    threshold: $judgeExpectation['threshold'],
                    judge: $judgeExpectation['judge'] ?? null,
                );
            } catch (Throwable $exception) {
                if ($attempt >= $this->retries || ! $this->shouldRetry($exception)) {
                    throw $exception;
                }

                $attempt++;
                $this->sleepBeforeRetry();
            }
        }
    }

    protected function sleepBeforeRetry(): void
    {
        if ($this->retrySleepMs <= 0) {
            return;
        }

        usleep($this->retrySleepMs * 1000);
    }

    protected function shouldRetry(Throwable $exception): bool
    {
        $class = strtolower($exception::class);
        $message = strtolower($exception->getMessage());
        $code = is_numeric($exception->getCode()) ? (int) $exception->getCode() : null;

        if ($code === 401 || str_contains($message, 'unauthorized')) {
            return false;
        }

        if ($code === 429 || ($code !== null && $code >= 500 && $code < 600)) {
            return true;
        }

        if (str_contains($class, 'connection') || str_contains($class, 'timeout')) {
            return true;
        }

        foreach (['timed out', 'timeout', 'temporar', 'rate limit', 'too many requests', 'connection', 'try again', 'service unavailable'] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int, cost?: float}  $base
     * @param  array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int, cost?: float}  $extra
     * @return array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int, cost?: float}
     */
    protected function mergeUsage(array $base, array $extra): array
    {
        foreach (['prompt_tokens', 'completion_tokens', 'total_tokens'] as $key) {
            $base[$key] = (int) ($base[$key] ?? 0) + (int) ($extra[$key] ?? 0);
        }

        $base['cost'] = (float) ($base['cost'] ?? 0.0) + (float) ($extra['cost'] ?? 0.0);

        return $base;
    }
}
