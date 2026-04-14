<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\LaravelAIEvaluation\Evaluation;

use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Judge\PromptJudgeClient;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Scoring\ContainsScorer;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Scoring\ExactScorer;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Scoring\JudgeScorer;
use RuntimeException;

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
    ): EvalResult {
        if ($contains === [] && $exact === null && $judgeExpectations === []) {
            throw new RuntimeException("AI eval '{$caseId}' must define at least one expectation.");
        }

        $resolvedAgent = is_string($agent) ? app()->make($agent) : $agent;

        if (! method_exists($resolvedAgent, 'prompt')) {
            throw new RuntimeException("AI eval '{$caseId}' agent must implement a prompt method.");
        }

        $response = $resolvedAgent->prompt($input);
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
            ];

            if (! $result['passed']) {
                $failures[] = sprintf(
                    'Judge expectation failed (score %.3f < %.3f): %s',
                    $result['score'],
                    $result['threshold'],
                    $result['reason'],
                );
            }
        }

        return new EvalResult($caseId, $input, $output, $failures, $expectationResults);
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
}
