<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;
use Tests\Fixtures\Agents\InternalOpsAssistantAgent;

return function (StandaloneEvalSuite $suite): void {
    $suite->eval('internal-ops-assistant-agent', function () {
        return AIEval::agent(InternalOpsAssistantAgent::class)
            ->input('Reply in one short sentence and include these exact tokens: Ops summary, priority=high, escalation sent.')
            ->expectContains(['Ops summary', 'priority=high', 'escalation sent'])
            ->run();
    });
};
