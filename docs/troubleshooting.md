# Troubleshooting

Use this page when an eval fails before it can score the agent output.

## No standalone eval files found

The standalone runner only loads files ending in `*.eval.php`.

Check that the file exists under the configured path:

```bash
php artisan ai-evals:run tests/AgentEvals
```

If you use a custom path, update `config/laravel-ai-evaluation.php`:

```php
'standalone' => [
    'path' => 'tests/AgentEvals',
],
```

## Agent cannot be resolved

Class-string agents are resolved through the Laravel container. Confirm the class name is correct and autoloadable:

```php
AIEval::agent(App\Ai\Agents\SupportAgent::class)
```

If the agent has constructor dependencies, make sure Laravel can resolve them.

## Agent must expose `prompt()`

The agent must implement `Laravel\Ai\Contracts\Agent` or expose a callable `prompt(string $prompt)` method.

Minimal compatible shape:

```php
final class SupportAgent
{
    public function prompt(string $prompt): string
    {
        return 'Refunds are available within 30 days.';
    }
}
```

## Authentication error

If you see an authentication error or `401`, check your provider keys.

Keep keys in `.env` locally and CI secrets remotely:

```dotenv
OPENAI_API_KEY=your-openai-key
```

Use the environment variable names expected by your Laravel AI provider configuration.

## Rate limits or `429`

Avoid parallel live eval runs and add conservative retries:

```dotenv
AI_EVAL_RETRIES=2
AI_EVAL_RETRY_SLEEP_MS=500
```

See [Dealing with rate limits](/dealing-with-rate-limits) for CI strategies.

## Judge returned invalid JSON

Judge agents must return JSON with `score` and `reason`:

```json
{"score":0.82,"reason":"Correct and clear."}
```

The score must be numeric and between `0` and `1`. Returning Markdown, prose, or malformed JSON can fail the eval before threshold scoring.

## Eval fails locally but passes in CI

Compare these inputs between environments:

- Provider API key and model configuration
- Prompt or agent config loaded from `.env`
- Retrieval data, database seed data, or fixture data
- `AI_EVAL_RETRIES` and `AI_EVAL_RETRY_SLEEP_MS`
- Package versions from `composer.lock`

For flaky live behavior, start by filtering one case:

```bash
php artisan ai-evals:run --filter="refund"
```

Then tighten the prompt or use more specific deterministic expectations.

## Eval has no expectations

Every eval must define at least one expectation:

```php
->expectContains('refund')
```

Use [Deterministic expectations](/deterministic-expectations) for exact requirements and [LLM-as-judge expectations](/llm-as-judge-expectations) for semantic quality checks.
