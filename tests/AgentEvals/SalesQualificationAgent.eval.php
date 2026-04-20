<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;
use Tests\Fixtures\Agents\SalesQualificationAgent;

return function (StandaloneEvalSuite $suite): void {
    $suite->eval('sales-qualification-agent', function () {
        return AIEval::agent(SalesQualificationAgent::class)
            ->input('Reply in one short sentence and include these exact tokens: company_size, timeline, score=high.')
            ->expectContains(['company_size', 'timeline', 'score=high'])
            ->run();
    });
};
