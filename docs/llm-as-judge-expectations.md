# LLM-as-judge expectations

Use judge expectations when semantic quality matters more than exact text matching.

## Default judge

The package ships with a default judge agent: `DefaultJudgeAgent`.

## Configure a custom judge agent (optional)

Override the default judge in `config/laravel-ai-evaluation.php`:

```php
return [
    'judge' => [
        'agent' => App\Ai\Agents\JudgeAgent::class,
        'threshold' => 0.7,
    ],
];
```

The judge agent must expose a `prompt(string $prompt)` method.

You can also pass a judge directly per expectation.

## Judge requirements

For a judge to work with this package, it must meet these requirements:

- It must be resolvable as a class string from the container, or passed as an object instance.
- It must implement `Laravel\Ai\Contracts\Agent` or expose `prompt(string $prompt)`.
- Its response must include valid JSON with:
  - `score` (numeric, between `0` and `1`)
  - `reason` (string)

Expected payload shape:

```json
{"score":0.82,"reason":"Mostly correct and clear; misses one policy detail."}
```

Important behavior notes:

- The package expects strict JSON and validates both required fields.
- If the response includes extra text, the parser attempts to extract the first JSON object, but returning plain JSON is strongly recommended.
- If `score` is outside `0..1`, missing, or non-numeric, the eval fails with a judge response error.

## Configure a different judge for eval expectations

Use `useJudge()` to avoid passing the same judge repeatedly:

```php
use LaravelAIEvaluation\AIEval;

AIEval::agent(SupportAgent::class)
    ->input('What is your refund policy?')
    ->useJudge(App\Ai\Agents\JudgeAgent::class)
    ->expectJudge('The answer should be clear and policy accurate.', threshold: 0.8)
    ->run()
    ->assertPasses();
```

## `expectJudgeAgainst`

Use a rubric (`criteria`) and a reference answer.

```php
use LaravelAIEvaluation\AIEval;

AIEval::agent(SupportAgent::class)
    ->input('What is your refund policy?')
    ->expectJudgeAgainst(
        reference: 'Refunds are available within 30 days of purchase.',
        criteria: 'The answer should be correct, concise, and mention the 30 day window.',
        threshold: 0.8,
        judge: App\Ai\Agents\JudgeAgent::class,
    )
    ->run()
    ->assertPasses();
```

## `expectJudge`

Use a rubric without a reference answer.

```php
use LaravelAIEvaluation\AIEval;

AIEval::agent(SupportAgent::class)
    ->input('Explain our refund policy in one sentence')
    ->expectJudge(
        criteria: 'The answer should be clear, helpful, and avoid uncertainty.',
        threshold: 0.75,
        judge: App\Ai\Agents\JudgeAgent::class,
    )
    ->run()
    ->assertPasses();
```

## Notes

- Judge score is expected between `0` and `1`.
- Any expectation below threshold fails the eval.
- In CI this causes hard-fail behavior when using Pest or `php artisan ai-evals:run`.
