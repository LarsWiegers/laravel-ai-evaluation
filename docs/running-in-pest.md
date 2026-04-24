# Run in Pest

This is the recommended way to run evaluations.

## 1) Put eval tests in `tests/AgentEvals`

Example `tests/AgentEvals/SupportAgentEvalTest.php`:

```php
<?php

declare(strict_types=1);

use App\Ai\Agents\SupportAgent;
use LaravelAIEvaluation\AIEval;

it('returns refund policy details', function () {
    AIEval::agent(SupportAgent::class)
        ->name('refund-policy')
        ->input('What is your refund policy?')
        ->expectContains(['refund', '30 days'])
        ->run()
        ->assertPasses();
});
```

## 2) Ensure Pest discovers this folder

In `tests/Pest.php`:

```php
pest()->extend(Tests\TestCase::class)->in('Feature', 'AgentEvals');
```

## 3) Run Pest

```bash
vendor/bin/pest
```

::: warning We do not recommend parallel runs for live evals
Avoid `vendor/bin/pest --parallel` for `tests/AgentEvals`. Parallel workers can burst API requests and trigger provider rate limits (`429`, `too many requests`). If this happens, follow [Dealing with rate limits](/dealing-with-rate-limits).
:::

When an eval fails, the test fails with a PHPUnit assertion message from `assertPasses()`.

## Use real provider keys safely

Live evals call real model APIs, so keep credentials outside your repository.

- Set provider API keys in `.env` for local development and in secret stores for CI.
- Do not commit keys to eval files, config files, or source control.
- Prefer a dedicated eval key (separate from production) with quota and spend limits.
- Run live evals without parallel workers to avoid burst traffic.

Example local `.env` setup:

```env
# Use the provider key names expected by your Laravel AI configuration.
OPENAI_API_KEY=your-openai-key
# ANTHROPIC_API_KEY=your-anthropic-key

AI_EVAL_RETRIES=1
AI_EVAL_RETRY_SLEEP_MS=250
AI_EVAL_SUMMARY=true
AI_EVAL_SUMMARY_FORMAT=text
AI_EVAL_SUMMARY_CURRENCY=USD
```

## Optional: verbose output during Pest runs

```env
AI_EVAL_VERBOSE=true
AI_EVAL_FORMAT=text
```

`AI_EVAL_FORMAT` supports `text` and `json`.
