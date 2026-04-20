<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;
use Tests\Fixtures\Agents\BillingInvoiceAgent;

return function (StandaloneEvalSuite $suite): void {
    $suite->eval('billing-invoice-agent', function () {
        return AIEval::agent(BillingInvoiceAgent::class)
            ->input('Reply in one short sentence and include these exact tokens: Invoice status, payment failed, update payment method.')
            ->expectContains(['Invoice status', 'payment failed', 'update payment method'])
            ->run();
    });
};
