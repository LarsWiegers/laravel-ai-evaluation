<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\LaravelAIEvaluation\Evaluation;

use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Scoring\ContainsScorer;
use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Scoring\ExactScorer;
use RuntimeException;

class EvalRunner
{
    public function __construct(
        protected ContainsScorer $containsScorer = new ContainsScorer,
        protected ExactScorer $exactScorer = new ExactScorer,
    ) {
    }

    /**
     * @param  array<int, string>  $contains
     */
    public function run(
        object|string $agent,
        string $caseId,
        string $input,
        array $contains = [],
        ?string $exact = null,
    ): EvalResult {
        if ($contains === [] && $exact === null) {
            throw new RuntimeException("AI eval '{$caseId}' must define at least one expectation.");
        }

        $resolvedAgent = is_string($agent) ? app()->make($agent) : $agent;

        if (! method_exists($resolvedAgent, 'prompt')) {
            throw new RuntimeException("AI eval '{$caseId}' agent must implement a prompt method.");
        }

        $response = $resolvedAgent->prompt($input);
        $output = $this->stringifyResponse($response);

        $failures = [];

        if ($contains !== []) {
            $missing = $this->containsScorer->missing($output, $contains);

            if ($missing !== []) {
                $failures[] = sprintf('Missing required substring(s): %s', implode(', ', $missing));
            }
        }

        if ($exact !== null && ! $this->exactScorer->matches($output, $exact)) {
            $failures[] = sprintf('Expected exact output "%s" but received "%s"', trim($exact), trim($output));
        }

        return new EvalResult($caseId, $input, $output, $failures);
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
