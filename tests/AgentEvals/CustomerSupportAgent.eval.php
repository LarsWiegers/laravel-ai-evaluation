<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;
use Tests\Fixtures\Agents\CustomerSupportAgent;

return function (StandaloneEvalSuite $suite): void {
    $suite->eval('customer-support-agent', function () {
        return AIEval::agent(CustomerSupportAgent::class)
            ->input('Reply in one short sentence and include these exact tokens: refunds, 30 days, support.')
            ->expectContains(['refunds', '30 days', 'support'])
            ->run();
    });
};
