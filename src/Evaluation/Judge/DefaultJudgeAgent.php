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

        throw new RuntimeException(
            'Unable to run the default judge agent. Install and configure Laravel AI, or pass a custom judge to expectJudge/expectJudgeAgainst.'
        );
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
