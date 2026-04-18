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

### 3) Create an eval

::: code-group

```bash [Pest]
mkdir -p tests/AgentEvals && touch tests/AgentEvals/SupportAgentEvalTest.php
```

```bash [Standalone]
mkdir -p tests/AgentEvals && touch tests/AgentEvals/SupportAgent.eval.php
```

```php [Pest]
<?php

declare(strict_types=1);

use App\Ai\Agents\SupportAgent;
use LaravelAIEvaluation\AIEval;

it('returns refund policy details', function () {
    AIEval::agent(SupportAgent::class)
        ->input('What is your refund policy?')
        ->expectContains(['refund', '30 days'])
        ->run()
        ->assertPasses();
});
```

```php [Standalone]
<?php

declare(strict_types=1);

use App\Ai\Agents\SupportAgent;
use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

return static function (StandaloneEvalSuite $suite): void {
    $suite->eval('returns refund policy details', static function () {
        return AIEval::agent(SupportAgent::class)
            ->input('What is your refund policy?')
            ->expectContains(['refund', '30 days'])
            ->run();
    });
};
```

:::

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

- [When to run evals](./when-to-run-evals)
- [Expectations overview](./expectations)
- [Deterministic expectations](./deterministic-expectations)
- [LLM-as-judge expectations](./llm-as-judge-expectations)
- [Run in Pest](./running-in-pest)
- [Run standalone](./running-standalone)
- [Run in CI](./running-in-ci)
