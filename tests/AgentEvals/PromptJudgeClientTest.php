<?php

declare(strict_types=1);

use LaravelAIEvaluation\Evaluation\Judge\PromptJudgeClient;

it('evaluates judge response from strict json payload', function () {
    $client = new PromptJudgeClient;

    $verdict = $client->evaluate(
        input: 'Question',
        actualOutput: 'Answer',
        criteria: 'Be correct.',
        judge: new class {
            public function prompt(string $prompt): string
            {
                return '{"score":0.84,"reason":"Meets the criteria."}';
            }
        },
    );

    expect($verdict->score)->toBe(0.84);
    expect($verdict->reason)->toBe('Meets the criteria.');
});

it('extracts json payload wrapped in extra text', function () {
    $client = new PromptJudgeClient;

    $verdict = $client->evaluate(
        input: 'Question',
        actualOutput: 'Answer',
        criteria: 'Be correct.',
        judge: new class {
            public function prompt(string $prompt): string
            {
                return "analysis\n{\"score\":0.7,\"reason\":\"Good enough.\"}\nend";
            }
        },
    );

    expect($verdict->score)->toBe(0.7);
    expect($verdict->reason)->toBe('Good enough.');
});

it('captures usage from judge response object', function () {
    $client = new PromptJudgeClient;

    $verdict = $client->evaluate(
        input: 'Question',
        actualOutput: 'Answer',
        criteria: 'Be correct.',
        judge: new class {
            public function prompt(string $prompt): object
            {
                return (object) [
                    'text' => '{"score":1,"reason":"Perfect."}',
                    'usage' => [
                        'prompt_tokens' => 5,
                        'completion_tokens' => 3,
                        'total_tokens' => 8,
                        'cost' => 0.0012,
                    ],
                ];
            }
        },
    );

    expect($verdict->usage)->toBe([
        'prompt_tokens' => 5,
        'completion_tokens' => 3,
        'total_tokens' => 8,
        'cost' => 0.0012,
    ]);
});

it('throws when judge response is not valid json', function () {
    $client = new PromptJudgeClient;

    $client->evaluate(
        input: 'Question',
        actualOutput: 'Answer',
        criteria: 'Be correct.',
        judge: new class {
            public function prompt(string $prompt): string
            {
                return 'not json';
            }
        },
    );
})->throws(RuntimeException::class, 'Judge response was not valid JSON.');

it('throws when judge score is outside 0 to 1', function () {
    $client = new PromptJudgeClient;

    $client->evaluate(
        input: 'Question',
        actualOutput: 'Answer',
        criteria: 'Be correct.',
        judge: new class {
            public function prompt(string $prompt): string
            {
                return '{"score":1.2,"reason":"Too high."}';
            }
        },
    );
})->throws(RuntimeException::class, 'Judge score must be between 0 and 1.');

it('throws with clear contract guidance for invalid judge objects', function () {
    $client = new PromptJudgeClient;

    $client->evaluate(
        input: 'Question',
        actualOutput: 'Answer',
        criteria: 'Be correct.',
        judge: new stdClass,
    );
})->throws(RuntimeException::class, 'must implement Laravel\\Ai\\Contracts\\Agent or expose a prompt(string $prompt) method.');
