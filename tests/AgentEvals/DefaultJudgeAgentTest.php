<?php

declare(strict_types=1);

use LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Judge\DefaultJudgeAgent;

it('prompts through laravel ai anonymous agent fallback', function () {
    $judge = new class extends DefaultJudgeAgent
    {
        protected function promptWithLaravelAiSdk(string $prompt): mixed
        {
            $agent = $this->makeAnonymousAgent('Laravel\\Ai\\AnonymousAgent');

            return $agent->prompt($prompt);
        }

        protected function makeAnonymousAgent(string $anonymousAgentClass): object
        {
            return new class {
                public function prompt(string $prompt): string
                {
                    return "stub:{$prompt}";
                }
            };
        }
    };

    expect($judge->prompt('judge this'))->toBe('stub:judge this');
});
