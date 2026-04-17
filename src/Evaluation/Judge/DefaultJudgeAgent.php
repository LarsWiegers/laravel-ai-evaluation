<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Judge;

use RuntimeException;

class DefaultJudgeAgent
{
    public function prompt(string $prompt): string
    {
        $aiFacade = 'Illuminate\\Support\\Facades\\AI';

        if (class_exists($aiFacade) && method_exists($aiFacade, 'prompt')) {
            $response = call_user_func([$aiFacade, 'prompt'], $prompt);

            return $this->stringifyResponse($response);
        }

        if (function_exists('\\ai')) {
            $client = call_user_func('ai');

            if (is_object($client) && method_exists($client, 'prompt')) {
                $response = $client->prompt($prompt);

                return $this->stringifyResponse($response);
            }
        }

        $response = $this->promptWithLaravelAiSdk($prompt);

        return $this->stringifyResponse($response);
    }

    protected function promptWithLaravelAiSdk(string $prompt): mixed
    {
        $anonymousAgentClass = 'Laravel\\Ai\\AnonymousAgent';

        if (class_exists($anonymousAgentClass)) {
            $agent = new $anonymousAgentClass(
                instructions: $this->judgeInstructions(),
            );

            if (! method_exists($agent, 'prompt')) {
                throw new RuntimeException('Laravel AI AnonymousAgent must implement a prompt method.');
            }

            return $agent->prompt($prompt);
        }

        if (trait_exists('Laravel\\Ai\\Promptable')) {
            $agentClass = $this->createPromptableJudgeAgent();
            $agent = new $agentClass;

            if (! method_exists($agent, 'prompt')) {
                throw new RuntimeException('Laravel AI Promptable fallback must implement a prompt method.');
            }

            return $agent->prompt($prompt);
        }

        throw new RuntimeException(
            'Unable to run the default judge agent. Laravel AI SDK classes were not found. Ensure laravel/ai is installed and configured.'
        );
    }

    protected function createPromptableJudgeAgent(): string
    {
        return get_class(eval(sprintf(
            'return new class { use \\Laravel\\Ai\\Promptable; public function instructions(): string { return %s; } };',
            var_export($this->judgeInstructions(), true),
        )));
    }

    protected function judgeInstructions(): string
    {
        return 'You are an evaluation judge. Respond only with JSON containing "score" (0.0-1.0) and "reason".';
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

        throw new RuntimeException('Unable to convert default judge response to string output.');
    }
}
