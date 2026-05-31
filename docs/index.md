---
layout: home

hero:
  name: Laravel AI Evaluation
  tagline: Make sure your agents respond how you want them to.
  actions:
    - theme: brand
      text: First Eval
      link: /first-eval
    - theme: alt
      text: Concepts
      link: /concepts


features:
  - title: Real model calls
    details: Evaluate actual AI behavior, not mocked responses.
    link: /when-to-run-evals
  - title: Standalone Artisan runner
    details: Run eval files via `php artisan ai-evals:run` without Pest or PHPUnit.
    link: /running-standalone
  - title: Pest native
    details: Run directly inside Pest from `tests/AgentEvals` with a fluent API.
    link: /running-in-pest
  - title: CI ready
    details: Evals hard-fail when expectations are not met.
    link: /running-in-ci
---

## Quick Start

For a guided walkthrough, start with [First eval in 5 minutes](/first-eval).

### 1) Install

```bash
composer require --dev larswiegers/laravel-ai-evaluation
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

```dotenv [Text]
AI_EVAL_SUMMARY=true
AI_EVAL_SUMMARY_FORMAT=text
AI_EVAL_SUMMARY_CURRENCY=USD
```

```dotenv [JSON]
AI_EVAL_SUMMARY=true
AI_EVAL_SUMMARY_FORMAT=json
AI_EVAL_SUMMARY_CURRENCY=USD
```

:::

### 6) Get the summary output

For standalone JSON, JUnit, and GitHub annotation reports, see [Output formats](/output-formats).

Run your evals and check the end of the output:

::: code-group

```text [Text]
$ vendor/bin/pest tests/AgentEvals

AI Eval Summary
Total: 13
Passed: 12
Failed: 1
Prompt tokens: 7842
Completion tokens: 1966
Total tokens: 9808
Estimated cost: USD 0.070000
```

```json [JSON]
$ php artisan ai-evals:run

{"type":"ai_eval_summary","total":13,"passed":12,"failed":1,"prompt_tokens":7842,"completion_tokens":1966,"total_tokens":9808,"estimated_cost":0.07,"currency":"USD"}
```

:::
