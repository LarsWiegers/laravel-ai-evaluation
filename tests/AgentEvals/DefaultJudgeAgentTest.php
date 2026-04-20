<?php

declare(strict_types=1);

use LaravelAIEvaluation\Evaluation\Judge\DefaultJudgeAgent;

it('prompts through promptable fallback when facade is unavailable', function () {
    $judge = new class extends DefaultJudgeAgent
    {
        protected function createPromptableJudgeAgent(): object
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
