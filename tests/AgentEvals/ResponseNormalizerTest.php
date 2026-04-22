<?php

declare(strict_types=1);

use LaravelAIEvaluation\Evaluation\Support\ResponseNormalizer;

it('extracts usage from response array with usage key', function () {
    $normalizer = new ResponseNormalizer;

    $usage = $normalizer->extractUsage([
        'usage' => [
            'input_tokens' => 9,
            'output_tokens' => 4,
            'total_tokens' => 13,
            'total_cost' => 0.003,
        ],
    ]);

    expect($usage)->toBe([
        'prompt_tokens' => 9,
        'completion_tokens' => 4,
        'total_tokens' => 13,
        'cost' => 0.003,
    ]);
});

it('extracts usage from response object usage object', function () {
    $normalizer = new ResponseNormalizer;

    $response = (object) [
        'usage' => (object) [
            'prompt_tokens' => '7',
            'completion_tokens' => '2',
            'cost' => '0.0009',
        ],
    ];

    $usage = $normalizer->extractUsage($response);

    expect($usage)->toBe([
        'prompt_tokens' => 7,
        'completion_tokens' => 2,
        'total_tokens' => 9,
        'cost' => 0.0009,
    ]);
});

it('returns empty usage for responses without usage payload', function () {
    $normalizer = new ResponseNormalizer;

    expect($normalizer->extractUsage('plain response'))->toBe([]);
});
