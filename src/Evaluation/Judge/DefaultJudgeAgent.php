<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation\Judge;

use LaravelAIEvaluation\Evaluation\Support\ResponseNormalizer;
use RuntimeException;

class DefaultJudgeAgent
{
    public function __construct(
        protected ?ResponseNormalizer $responseNormalizer = null,
    ) {
        $this->responseNormalizer = $this->responseNormalizer ?? new ResponseNormalizer;
    }

    public function prompt(string $prompt): string
    {
        $aiFacade = 'Illuminate\\Support\\Facades\\AI';

        if (class_exists($aiFacade) && method_exists($aiFacade, 'prompt')) {
            $response = call_user_func([$aiFacade, 'prompt'], $prompt);

            return $this->responseNormalizer->stringifyResponse($response, 'default judge');
        }

        $response = $this->createPromptableJudgeAgent()->prompt($prompt);

        return $this->responseNormalizer->stringifyResponse($response, 'default judge');
    }

    protected function createPromptableJudgeAgent(): object
    {
        if (! trait_exists('Laravel\\Ai\\Promptable')) {
            throw new RuntimeException(
                'Unable to run the default judge agent. Laravel AI Promptable trait was not found. Ensure laravel/ai is installed and configured.'
            );
        }

        $instructions = $this->judgeInstructions();

        return new class($instructions)
        {
            use \Laravel\Ai\Promptable;

            public function __construct(
                protected string $instructions,
            ) {
            }

            public function instructions(): string
            {
                return $this->instructions;
            }
        };
    }

    protected function judgeInstructions(): string
    {
        return 'You are an evaluation judge. Respond only with JSON containing "score" (0.0-1.0) and "reason".';
    }

}
