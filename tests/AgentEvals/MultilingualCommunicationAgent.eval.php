<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;
use Tests\Fixtures\Agents\MultilingualCommunicationAgent;

return function (StandaloneEvalSuite $suite): void {
    $suite->eval('multilingual-communication-agent', function () {
        return AIEval::agent(MultilingualCommunicationAgent::class)
            ->input('Reply in one short sentence and include these exact tokens: Translation (es), Tone: friendly, next steps.')
            ->expectContains(['Translation (es)', 'Tone: friendly', 'next steps'])
            ->run();
    });
};
