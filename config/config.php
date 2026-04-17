<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'verbose' => env('AI_EVAL_VERBOSE', false),
    'format' => env('AI_EVAL_FORMAT', 'text'),

    'summary' => [
        'enabled' => env('AI_EVAL_SUMMARY', false),
        'format' => env('AI_EVAL_SUMMARY_FORMAT', env('AI_EVAL_FORMAT', 'text')),
        'currency' => env('AI_EVAL_SUMMARY_CURRENCY', 'USD'),
    ],

    'judge' => [
        'agent' => LaravelAIEvaluation\LaravelAIEvaluation\Evaluation\Judge\DefaultJudgeAgent::class,
        'threshold' => 0.7,
    ],

    'standalone' => [
        'path' => 'tests/AgentEvals',
    ],
];
