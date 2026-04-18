<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation\Judge;

use LaravelAIEvaluation\Evaluation\Support\ResponseNormalizer;
use RuntimeException;

class PromptJudgeClient implements JudgeClient
{
    public function __construct(
        protected ?ResponseNormalizer $responseNormalizer = null,
    ) {
        $this->responseNormalizer = $this->responseNormalizer ?? new ResponseNormalizer;
    }

    public function evaluate(
        string $input,
        string $actualOutput,
        string $criteria,
        ?string $reference = null,
        object|string|null $judge = null,
    ): JudgeVerdict {
        $judgeAgent = $judge ?? config('laravel-ai-evaluation.judge.agent');

        if (! is_string($judgeAgent) && ! is_object($judgeAgent)) {
            throw new RuntimeException('Judge agent is not configured. Set laravel-ai-evaluation.judge.agent first.');
        }

        $resolvedJudgeAgent = is_string($judgeAgent) ? app()->make($judgeAgent) : $judgeAgent;

        if (! method_exists($resolvedJudgeAgent, 'prompt')) {
            throw new RuntimeException('Configured judge agent must implement a prompt method.');
        }

        $judgePrompt = $this->buildJudgePrompt($input, $actualOutput, $criteria, $reference);
        $response = $resolvedJudgeAgent->prompt($judgePrompt);
        $usage = $this->responseNormalizer->extractUsage($response);
        $raw = $this->responseNormalizer->stringifyResponse($response, 'judge');
        $payload = $this->decodePayload($raw);

        if (! isset($payload['score']) || ! is_numeric($payload['score'])) {
            throw new RuntimeException('Judge response must contain numeric "score" field.');
        }

        if (! isset($payload['reason']) || ! is_string($payload['reason'])) {
            throw new RuntimeException('Judge response must contain string "reason" field.');
        }

        $score = (float) $payload['score'];

        if ($score < 0 || $score > 1) {
            throw new RuntimeException('Judge score must be between 0 and 1.');
        }

        return new JudgeVerdict($score, trim($payload['reason']), $usage);
    }

    protected function buildJudgePrompt(
        string $input,
        string $actualOutput,
        string $criteria,
        ?string $reference,
    ): string {
        $referenceBlock = $reference === null
            ? "Reference:\nNone provided"
            : "Reference:\n{$reference}";

        return <<<PROMPT
You are an evaluation judge. Score the model output using the provided criteria.

Return STRICT JSON only with this exact schema:
{"score": <float between 0 and 1>, "reason": "<short reason>"}

Scoring instructions:
- 1.0 means criteria fully satisfied.
- 0.0 means criteria not satisfied.
- Be strict and concise.

Criteria:
{$criteria}

Input:
{$input}

Model Output:
{$actualOutput}

{$referenceBlock}
PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodePayload(string $response): array
    {
        $decoded = json_decode($response, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($response, '{');
        $end = strrpos($response, '}');

        if ($start === false || $end === false || $end <= $start) {
            throw new RuntimeException('Judge response was not valid JSON.');
        }

        $json = substr($response, $start, $end - $start + 1);
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Judge response was not valid JSON.');
        }

        return $decoded;
    }

}
