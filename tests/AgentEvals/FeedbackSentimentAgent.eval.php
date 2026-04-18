<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;
use Tests\Fixtures\Agents\FeedbackSentimentAgent;

return function (StandaloneEvalSuite $suite): void {
    $suite->eval('feedback-sentiment-agent', function () {
        return AIEval::agent(FeedbackSentimentAgent::class)
            ->input('Reply in one short sentence and include these exact tokens: sentiment=negative, theme=delivery delay, follow_up.')
            ->expectContains(['sentiment=negative', 'theme=delivery delay', 'follow_up'])
            ->run();
    });
};
