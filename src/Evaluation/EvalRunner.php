<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation;

use LaravelAIEvaluation\Evaluation\Judge\PromptJudgeClient;
use LaravelAIEvaluation\Evaluation\Scoring\ContainsScorer;
use LaravelAIEvaluation\Evaluation\Scoring\ExactScorer;
use LaravelAIEvaluation\Evaluation\Scoring\JudgeScorer;
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
    }

    /**
     * @param  array<int, string>  $contains
     * @param  array<int, array{criteria: string, reference: string|null, threshold: float|null, judge: object|string|null}>  $judgeExpectations
     */
    public function run(
        object|string $agent,
        ?string $name,
        string $input,
        array $contains = [],
        ?string $exact = null,
        array $judgeExpectations = [],
        ?string $location = null,
    ): EvalResult {
        $name = $this->resolveName($name);

        if ($contains === [] && $exact === null && $judgeExpectations === []) {
            throw new RuntimeException("AI eval '{$name}' must define at least one expectation.");
        }

        $resolvedAgent = is_string($agent) ? app()->make($agent) : $agent;

        if (! method_exists($resolvedAgent, 'prompt')) {
            throw new RuntimeException("AI eval '{$name}' agent must implement a prompt method.");
        }

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
