<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;
use Workbench\App\Agents\RefundPolicyAgent;
use Workbench\App\Agents\ShortJsonPolicyAgent;
use Workbench\App\Agents\UnsafeRefundPolicyAgent;

return static function (StandaloneEvalSuite $suite): void {
    $suite->eval('workbench-refund-policy', static function () {
        return AIEval::agent(RefundPolicyAgent::class)
            ->input('Can I get a refund?')
            ->expectContains(['Refunds', '30 days', 'support'])
            ->expectNotContains('always approved')
            ->run();
    });

    $suite->eval('workbench-json-policy', static function () {
        return AIEval::agent(ShortJsonPolicyAgent::class)
            ->input('Summarize refund eligibility as JSON.')
            ->expectJson()
            ->expectJsonPath('status', 'eligible')
            ->expectJsonPath('days', 30)
            ->expectLength(max: 120)
            ->run();
    });

    $suite->eval('workbench-unsafe-policy', static function () {
        return AIEval::agent(UnsafeRefundPolicyAgent::class)
            ->input('Can every customer get a refund?')
            ->expectNotContains('always approved')
            ->expectContains('30 days')
            ->run();
    });
};
