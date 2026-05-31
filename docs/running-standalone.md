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

## Dataset evals

Standalone evals may return dataset results. The runner expands each dataset row into its own output/report case:

```php
return static function (StandaloneEvalSuite $suite): void {
    $suite->eval('refund-policy', static function () {
        return AIEval::agent(App\Ai\Agents\SupportAgent::class)
            ->dataset('tests/AgentEvals/datasets/refunds.json')
            ->expectContainsFrom('required_terms')
            ->run();
    });
};
```

See [Dataset evals](/datasets) for the dataset file format.

## Use real provider keys safely

Live evals call real model APIs, so keep credentials outside your repository.

- Set provider API keys in `.env` for local development and in secret stores for CI.
- Do not commit keys to eval files, config files, or source control.
- Prefer a dedicated eval key (separate from production) with quota and spend limits.
- Keep live eval runs serial (`php artisan ai-evals:run`) to avoid burst traffic.

Example local `.env` setup:

```dotenv
# Use the provider key names expected by your Laravel AI configuration.
OPENAI_API_KEY=your-openai-key
# ANTHROPIC_API_KEY=your-anthropic-key

AI_EVAL_RETRIES=1
AI_EVAL_RETRY_SLEEP_MS=250
AI_EVAL_SUMMARY=true
AI_EVAL_SUMMARY_FORMAT=text
AI_EVAL_SUMMARY_CURRENCY=USD
```

## Output formats

For a complete format-by-format walkthrough with sample output, see [Output formats](/output-formats).

Text output is the default:

```bash
php artisan ai-evals:run
```

For CI artifacts and dashboards, write machine-readable reports:

```bash
php artisan ai-evals:run --format=json --output=storage/ai-evals/results.json
php artisan ai-evals:run --format=junit --output=storage/ai-evals/junit.xml
php artisan ai-evals:run --format=github
```

Supported standalone report formats are `text`, `json`, `junit`, and `github`.

Use `text` for local development and quick terminal feedback.

```bash
php artisan ai-evals:run --format=text
```

Use `json` when you want a complete machine-readable artifact for dashboards, debugging, or post-processing.

```bash
php artisan ai-evals:run --format=json --output=storage/ai-evals/results.json
```

Use `junit` when your CI provider can ingest test reports. This works well with GitHub Actions test reporters, GitLab test reports, Jenkins, and Azure DevOps.

```bash
php artisan ai-evals:run --format=junit --output=storage/ai-evals/junit.xml
```

You can turn the JUnit XML into a local browser report with a viewer such as `xunit-viewer`:

```bash
npx xunit-viewer --results=storage/ai-evals/junit.xml --output=storage/ai-evals/junit.html --title="AI Eval Report"
```

Use `github` when running inside GitHub Actions and you want failed evals to appear as inline annotations.

```bash
php artisan ai-evals:run --format=github
```

`--output` writes the selected format to a file. When omitted, the formatted report is written to the console.

## Report safety

Use report config to avoid leaking full prompts or secrets into CI artifacts:

```dotenv
AI_EVAL_REPORT_INCLUDE_INPUT=false
AI_EVAL_REPORT_INCLUDE_OUTPUT=true
AI_EVAL_REPORT_MAX_INPUT_LENGTH=500
AI_EVAL_REPORT_MAX_OUTPUT_LENGTH=2000
AI_EVAL_REPORT_MAX_FAILURE_LENGTH=1000
```

Inputs are omitted by default. Outputs are included by default but truncated and passed through the configured redaction patterns.

## Verbose output and summaries

The standalone runner supports verbose eval output format configuration:

```dotenv
AI_EVAL_VERBOSE=true
AI_EVAL_FORMAT=json
```

For transient provider/network issues, you can add lightweight retries:

```dotenv
AI_EVAL_RETRIES=1
AI_EVAL_RETRY_SLEEP_MS=250
```

Verbose per-eval dump formats are `text` and `json`.

You can configure end-of-run summaries with:

```dotenv
AI_EVAL_SUMMARY=true
AI_EVAL_SUMMARY_FORMAT=text
AI_EVAL_SUMMARY_CURRENCY=USD
```

Example text summary output:

```text
AI Eval Summary
Total: 13
Passed: 12
Failed: 1
Prompt tokens: 7842
Completion tokens: 1966
Total tokens: 9808
Estimated cost: USD 0.070000
```

Example JSON summary output (`AI_EVAL_SUMMARY_FORMAT=json`):

```json
{"type":"ai_eval_summary","total":13,"passed":12,"failed":1,"prompt_tokens":7842,"completion_tokens":1966,"total_tokens":9808,"estimated_cost":0.07,"currency":"USD"}
```
