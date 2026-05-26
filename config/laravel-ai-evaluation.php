<?php

return [
    'verbose' => env('AI_EVAL_VERBOSE', false),
    'format' => env('AI_EVAL_FORMAT', 'text'),
    'retries' => env('AI_EVAL_RETRIES', 0),
    'retry_sleep_ms' => env('AI_EVAL_RETRY_SLEEP_MS', 0),

    'summary' => [
        'enabled' => env('AI_EVAL_SUMMARY', false),
        'format' => env('AI_EVAL_SUMMARY_FORMAT', env('AI_EVAL_FORMAT', 'text')),
        'currency' => env('AI_EVAL_SUMMARY_CURRENCY', 'USD'),
    ],

    'judge' => [
        'agent' => LaravelAIEvaluation\Evaluation\Judge\DefaultJudgeAgent::class,
        'threshold' => 0.7,
    ],

    'standalone' => [
        'path' => 'tests/AgentEvals',
        'report' => [
            'include_input' => env('AI_EVAL_REPORT_INCLUDE_INPUT', false),
            'include_output' => env('AI_EVAL_REPORT_INCLUDE_OUTPUT', true),
            'max_input_length' => env('AI_EVAL_REPORT_MAX_INPUT_LENGTH', 500),
            'max_output_length' => env('AI_EVAL_REPORT_MAX_OUTPUT_LENGTH', 2000),
            'max_failure_length' => env('AI_EVAL_REPORT_MAX_FAILURE_LENGTH', 1000),
            'redact_patterns' => [
                '/sk-[A-Za-z0-9_\-]{20,}/',
                '/(api[_-]?key|token|secret)\s*[:=]\s*["\']?[^\s"\']+/i',
            ],
        ],
    ],
];
