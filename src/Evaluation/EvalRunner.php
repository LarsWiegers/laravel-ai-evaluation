<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\LaravelAIEvaluation\Evaluation;

use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Judge\PromptJudgeClient;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Scoring\ContainsScorer;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Scoring\ExactScorer;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Scoring\JudgeScorer;
use RuntimeException;
use Throwable;

class EvalRunner
{
    public function __construct(
        protected ContainsScorer $containsScorer = new ContainsScorer,
        protected ExactScorer $exactScorer = new ExactScorer,
        protected ?JudgeScorer $judgeScorer = null,
    ) {
        $this->judgeScorer = $this->judgeScorer ?? new JudgeScorer(
            new PromptJudgeClient,
            (float) config('laravel-ai-evaluation.judge.threshold', 0.7),
        );
    }

    /**
     * @param  array<int, string>  $contains
     * @param  array<int, array{criteria: string, reference: string|null, threshold: float|null, judge: object|string|null}>  $judgeExpectations
     */
    public function run(
        object|string $agent,
        string $caseId,
        string $input,
        array $contains = [],
        ?string $exact = null,
        array $judgeExpectations = [],
        ?string $location = null,
    ): EvalResult {
        if ($contains === [] && $exact === null && $judgeExpectations === []) {
            throw new RuntimeException("AI eval '{$caseId}' must define at least one expectation.");
        }

        $resolvedAgent = is_string($agent) ? app()->make($agent) : $agent;

        if (! method_exists($resolvedAgent, 'prompt')) {
            throw new RuntimeException("AI eval '{$caseId}' agent must implement a prompt method.");
        }

        try {
            $response = $resolvedAgent->prompt($input);
        } catch (Throwable $exception) {
            if ($this->isAuthenticationFailure($exception)) {
                throw new RuntimeException(
                    "AI eval '{$caseId}' failed: Authentication error. Check your AI provider API key is configured.",
                    0,
                    $exception,
                );
            }

            throw $exception;
        }

        $usage = $this->extractUsage($response);
        $output = $this->stringifyResponse($response);

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
            $result = $this->judgeScorer->score(
                input: $input,
                actualOutput: $output,
                criteria: $judgeExpectation['criteria'],
                reference: $judgeExpectation['reference'],
                threshold: $judgeExpectation['threshold'],
                judge: $judgeExpectation['judge'] ?? null,
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

        $result = new EvalResult($caseId, $input, $output, $failures, $expectationResults, $location, $usage);

        EvalRunSummary::record($result);

        if ((bool) config('laravel-ai-evaluation.verbose', false)) {
            $result->dump(format: (string) config('laravel-ai-evaluation.format', 'text'));
        }

        return $result;
    }

    protected function isAuthenticationFailure(Throwable $exception): bool
    {
        if ($exception->getCode() === 401) {
            return true;
        }

        return str_contains(strtolower($exception->getMessage()), '401');
    }

    protected function stringifyResponse(mixed $response): string
    {
        if (is_string($response)) {
            return $response;
        }

        if (is_scalar($response)) {
            return (string) $response;
        }

        if (is_object($response) && method_exists($response, '__toString')) {
            return (string) $response;
        }

        if (is_object($response) && property_exists($response, 'text') && is_string($response->text)) {
            return $response->text;
        }

        throw new RuntimeException('Unable to convert AI response to string output for evaluation.');
    }

    /**
     * @return array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int, cost?: float}
     */
    protected function extractUsage(mixed $response): array
    {
        $source = null;

        if (is_array($response) && isset($response['usage']) && is_array($response['usage'])) {
            $source = $response['usage'];
        }

        if (is_object($response) && isset($response->usage)) {
            if (is_array($response->usage)) {
                $source = $response->usage;
            }

            if (is_object($response->usage)) {
                $source = get_object_vars($response->usage);
            }
        }

        if (! is_array($source)) {
            return [];
        }

        $prompt = $source['prompt_tokens'] ?? $source['input_tokens'] ?? null;
        $completion = $source['completion_tokens'] ?? $source['output_tokens'] ?? null;
        $total = $source['total_tokens'] ?? null;
        $cost = $source['cost'] ?? $source['total_cost'] ?? null;

        $usage = [];

        if (is_numeric($prompt)) {
            $usage['prompt_tokens'] = (int) $prompt;
        }

        if (is_numeric($completion)) {
            $usage['completion_tokens'] = (int) $completion;
        }

        if (is_numeric($total)) {
            $usage['total_tokens'] = (int) $total;
        }

        if (is_numeric($cost)) {
            $usage['cost'] = (float) $cost;
        }

        return $usage;
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
