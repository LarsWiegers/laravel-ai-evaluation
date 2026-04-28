# Run standalone

If you want to run AI evals without Pest or PHPUnit, use the built-in Artisan command.

## Run all standalone evals

```bash
php artisan ai-evals:run
```

## Run a different folder

```bash
php artisan ai-evals:run tests/AgentEvals/Billing
```

## Useful filters

Run matching eval cases:

```bash
php artisan ai-evals:run --filter="refund policy"
```

By default, the command runs `tests/AgentEvals`.

To customize the default standalone folder, set:

```php
// config/laravel-ai-evaluation.php
'standalone' => [
    'path' => 'tests/AgentEvals',
],
```

## Standalone eval file format

Each standalone eval file should use a `*.eval.php` filename and return a callable that receives `StandaloneEvalSuite` and registers one or more eval cases.

```php
<?php

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

return static function (StandaloneEvalSuite $suite): void {
    $suite->eval('refund-policy', static function () {
        return AIEval::agent(App\Ai\Agents\SupportAgent::class)
            ->input('What is your refund policy?')
            ->expectContains(['refund', '30 days'])
            ->run();
    });
};
```

## Use real provider keys safely

Live evals call real model APIs, so keep credentials outside your repository.

- Set provider API keys in `.env` for local development and in secret stores for CI.
- Do not commit keys to eval files, config files, or source control.
- Prefer a dedicated eval key (separate from production) with quota and spend limits.
- Keep live eval runs serial (`php artisan ai-evals:run`) to avoid burst traffic.

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

## Output and summary options

The standalone runner supports verbose eval output format configuration:

```env
AI_EVAL_VERBOSE=true
AI_EVAL_FORMAT=json
```

For transient provider/network issues, you can add lightweight retries:

```env
AI_EVAL_RETRIES=1
AI_EVAL_RETRY_SLEEP_MS=250
```

Supported formats are `text` and `json`.

You can configure end-of-run summaries with:

```env
AI_EVAL_SUMMARY=true
AI_EVAL_SUMMARY_FORMAT=text
AI_EVAL_SUMMARY_CURRENCY=USD
```

Example text summary output:

```text
AI Eval Summary
Passed: 12
Failed: 1
Prompt tokens: 7,842
Completion tokens: 1,966
Total tokens: 9,808
Estimated cost: $0.07 USD
```

Example JSON summary output (`AI_EVAL_SUMMARY_FORMAT=json`):

```json
{"passed":12,"failed":1,"tokens":{"prompt":7842,"completion":1966,"total":9808},"cost":{"amount":0.07,"currency":"USD"}}
```
