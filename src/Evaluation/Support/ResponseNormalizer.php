<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation\Support;

use LaravelAIEvaluation\Evaluation\ToolCall;
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

    /**
     * @return array<int, ToolCall>
     */
    public function extractToolCalls(mixed $response): array
    {
        $source = null;

        if (is_array($response) && isset($response['tool_calls'])) {
            $source = $response['tool_calls'];
        }

        if ($source === null && is_array($response) && isset($response['toolCalls'])) {
            $source = $response['toolCalls'];
        }

        if ($source === null && is_object($response) && isset($response->toolCalls)) {
            $source = $response->toolCalls;
        }

        if (is_object($source) && method_exists($source, 'all')) {
            $source = $source->all();
        }

        if (! is_iterable($source)) {
            return [];
        }

        $toolCalls = [];

        foreach ($source as $toolCall) {
            $normalized = $this->normalizeToolCall($toolCall);

            if ($normalized !== null) {
                $toolCalls[] = $normalized;
            }
        }

        return $toolCalls;
    }

    protected function normalizeToolCall(mixed $toolCall): ?ToolCall
    {
        if ($toolCall instanceof ToolCall) {
            return $toolCall;
        }

        if (is_object($toolCall) && method_exists($toolCall, 'toArray')) {
            $toolCall = $toolCall->toArray();
        } elseif (is_object($toolCall)) {
            $toolCall = get_object_vars($toolCall);
        }

        if (! is_array($toolCall)) {
            return null;
        }

        $name = $toolCall['name'] ?? $toolCall['tool_name'] ?? $toolCall['function']['name'] ?? null;

        if (! is_string($name) || $name === '') {
            return null;
        }

        $arguments = $toolCall['arguments'] ?? $toolCall['args'] ?? $toolCall['function']['arguments'] ?? [];

        if (is_string($arguments)) {
            $decoded = json_decode($arguments, true);
            $arguments = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
        }

        if (! is_array($arguments)) {
            $arguments = [];
        }

        $id = $toolCall['id'] ?? $toolCall['tool_call_id'] ?? null;

        return new ToolCall($name, $arguments, is_scalar($id) ? (string) $id : null, 'response');
    }
}
