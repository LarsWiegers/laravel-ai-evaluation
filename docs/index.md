---
layout: home

hero:
  name: Laravel AI Evaluation
  text: Real-call LLM evals for Laravel AI
  tagline: Validate model output quality in Laravel with Pest or standalone eval files.
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
php artisan ai-evals:make refund-policy --type=pest
```

```bash [Standalone]
php artisan ai-evals:make refund-policy --type=standalone
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

## Learn More

- [Installation](./installation)
- [When to run evals](./when-to-run-evals)
- [Create eval files](./creating-evals)
- [Expectations overview](./expectations)
- [Deterministic expectations](./deterministic-expectations)
- [LLM-as-judge expectations](./llm-as-judge-expectations)
- [Run in Pest](./running-in-pest)
- [Run standalone](./running-standalone)
- [Run in CI](./running-in-ci)
