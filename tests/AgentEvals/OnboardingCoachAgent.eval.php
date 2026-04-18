<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;
use Tests\Fixtures\Agents\OnboardingCoachAgent;

return function (StandaloneEvalSuite $suite): void {
    $suite->eval('onboarding-coach-agent', function () {
        return AIEval::agent(OnboardingCoachAgent::class)
            ->input('Reply in one short sentence and include these exact tokens: Onboarding checklist, invite your team, first workflow.')
            ->expectContains(['Onboarding checklist', 'invite your team', 'first workflow'])
            ->run();
    });
};
