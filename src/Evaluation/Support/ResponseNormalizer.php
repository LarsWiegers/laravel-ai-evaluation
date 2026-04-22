<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation\Support;

use RuntimeException;

class ResponseNormalizer
{
    public function stringifyResponse(mixed $response, string $context): string
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

        throw new RuntimeException(sprintf('Unable to convert %s response to string output.', $context));
    }

    /**
     * @return array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int, cost?: float}
     */
    public function extractUsage(mixed $response): array
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

        $prompt ??= $source['promptTokens'] ?? null;
        $completion ??= $source['completionTokens'] ?? null;
        $total ??= $source['totalTokens'] ?? null;
        $cost ??= $source['totalCost'] ?? null;

        $usage = [];

        if (is_numeric($prompt)) {
            $usage['prompt_tokens'] = (int) $prompt;
        }

        if (is_numeric($completion)) {
            $usage['completion_tokens'] = (int) $completion;
        }

        if (is_numeric($total)) {
            $usage['total_tokens'] = (int) $total;
        } elseif (isset($usage['prompt_tokens'], $usage['completion_tokens'])) {
            $usage['total_tokens'] = $usage['prompt_tokens'] + $usage['completion_tokens'];
        }

        if (is_numeric($cost)) {
            $usage['cost'] = (float) $cost;
        }

        return $usage;
    }
}
