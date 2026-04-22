---
layout: home

hero:
  name: Laravel AI Evaluation
  text: Real-call LLM evals for Laravel AI
  tagline: Make sure your agents respond how you want them to.
  actions:
    - theme: brand
      text: Run in Pest
      link: /running-in-pest
    - theme: alt
      text: Run Standalone
      link: /running-standalone

features:
  - title: Real model calls
    details: Evaluate actual AI behavior, not mocked responses.
  - title: Pest native
    details: Run directly inside Pest from `tests/AgentEvals` with a fluent API.
  - title: Standalone Artisan runner
    details: Run eval files via `php artisan ai-evals:run` without Pest or PHPUnit.
  - title: Output control
    details: Use text or JSON output with verbose mode and run summaries.
  - title: CI ready
    details: Evals hard-fail when expectations are not met.
---

## Quick Start

### 1) Install

```bash
composer require larswiegers/laravel-ai-evaluation
```

### 2) Configure your run mode

::: code-group

```php [Pest]
pest()->extend(Tests\TestCase::class)->in('Feature', 'AgentEvals');
```

```text [Standalone]
No additional setup is required.
```

:::

### 3) Generate an eval file

::: code-group

```bash [Pest]
php artisan make:ai-evals refund-policy --type=pest
```

```bash [Standalone]
php artisan make:ai-evals refund-policy --type=standalone
```

:::

The command scaffolds a starter file you can edit for your agent and expectations.

### 4) Run

::: code-group

```bash [Pest]
vendor/bin/pest tests/AgentEvals
```

```bash [Standalone]
php artisan ai-evals:run
```

:::

### 5) Configure summary output

Enable summaries and choose the format in your `.env` (or CI environment):

::: code-group

```env [Text]
AI_EVAL_SUMMARY=true
AI_EVAL_SUMMARY_FORMAT=text
AI_EVAL_SUMMARY_CURRENCY=USD
```

```env [JSON]
AI_EVAL_SUMMARY=true
AI_EVAL_SUMMARY_FORMAT=json
AI_EVAL_SUMMARY_CURRENCY=USD
```

:::

### 6) Get the summary output

Run your evals and check the end of the output:

::: code-group

```text [Text]
$ vendor/bin/pest tests/AgentEvals

AI Eval Summary
Passed: 12
Failed: 1
Prompt tokens: 7,842
Completion tokens: 1,966
Total tokens: 9,808
Estimated cost: $0.07 USD
```

```json [JSON]
$ php artisan ai-evals:run

{"passed":12,"failed":1,"tokens":{"prompt":7842,"completion":1966,"total":9808},"cost":{"amount":0.07,"currency":"USD"}}
```

:::
