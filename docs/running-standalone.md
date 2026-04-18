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
