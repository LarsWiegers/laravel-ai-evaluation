<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'judge' => [
        'agent' => LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Judge\DefaultJudgeAgent::class,
        'threshold' => 0.7,
    ],

    'standalone' => [
        'path' => 'tests/AgentEvals',
    ],
];
