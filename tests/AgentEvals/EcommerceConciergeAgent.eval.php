<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;
use Tests\Fixtures\Agents\EcommerceConciergeAgent;

return function (StandaloneEvalSuite $suite): void {
    $suite->eval('ecommerce-concierge-agent', function () {
        return AIEval::agent(EcommerceConciergeAgent::class)
            ->input('Reply in one short sentence and include these exact tokens: recommendation, shipping, returns.')
            ->expectContains(['recommendation', 'shipping', 'returns'])
            ->run();
    });
};
