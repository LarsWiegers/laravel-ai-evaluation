<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;
use Tests\Fixtures\Agents\KnowledgeBaseAgent;

return function (StandaloneEvalSuite $suite): void {
    $suite->eval('knowledge-base-agent', function () {
        return AIEval::agent(KnowledgeBaseAgent::class)
            ->input('Reply in one short sentence and include these exact tokens: knowledge base, SSO setup, Source:.')
            ->expectContains(['knowledge base', 'SSO setup', 'Source:'])
            ->run();
    });
};
