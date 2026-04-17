# LLM-as-judge expectations

Use judge expectations when semantic quality matters more than exact text matching.

## Default judge

The package ships with a default judge agent: `DefaultJudgeAgent`.

If Laravel AI is available, it is used automatically so you can start without creating a custom judge.
The default judge supports Laravel AI SDK variants that expose either `AnonymousAgent` or only `Promptable`.

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

## Configure one judge for multiple expectations

Use `useJudge()` to avoid passing the same judge repeatedly:

```php
LaravelAIEvaluation::agent(SupportAgent::class)
    ->input('What is your refund policy?')
    ->useJudge(App\Ai\Agents\JudgeAgent::class)
    ->expectJudge('The answer should be clear and policy accurate.', threshold: 0.8)
    ->expectJudgeAgainst(
        reference: 'Refunds are available within 30 days of purchase.',
        criteria: 'The answer should match the policy and mention the timeframe.',
        threshold: 0.8,
    )
    ->run()
    ->assertPasses();
```

## `expectJudgeAgainst`

Use a rubric (`criteria`) and a reference answer.

```php
LaravelAIEvaluation::agent(SupportAgent::class)
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
LaravelAIEvaluation::agent(SupportAgent::class)
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
