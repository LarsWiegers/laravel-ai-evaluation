---
name: create-ai-eval
description: >-
  Guides AI agents to create ai-evals and use the "AI Eval" package for evaluating AI agent performance.
author: Lars Wiegers
---

# Package Installation

## When to apply

Use this skill when:

- Laravel AI agents need to be tested on actual output. 
- Agents need to be evaluated on their performance in a structured and reproducible way.
- You want to leverage the "AI Eval" package for evaluating AI agent performance.

## Instructions

- Install the "AI Eval" package if not already used, install using Composer and follow the installation instructions in the package documentation
- Read the package documentation to understand how to create evals and use them for testing AI agents.

```bash
composer require larswiegers/laravel-ai-eval
```

## Example

```bash
composer require --dev larswiegers/laravel-ai-evaluation
```

The service provider is auto-discovered by Laravel.

### Install package defaults

Publish config and ensure the default eval directory exists:

```bash
php artisan ai-evals:install
```

### Next step

Generate your first eval file:

```bash
php artisan make:ai-evals refund-policy --type=pest
```

Or standalone:

```bash
php artisan make:ai-evals refund-policy --type=standalone
```

### Default test case (Pest)

Make sure your `tests/Pest.php` includes `AgentEvals` so Pest discovers generated eval tests:

```php
<?php

declare(strict_types=1);

use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature', 'AgentEvals');
```

If you use a custom base test case, replace `Tests\TestCase` with your project test case class.

### Optional: publish config

If you want to customize defaults (format, retries, summary, judge agent), publish the config file:

```bash
php artisan vendor:publish --tag=laravel-ai-evaluation-config
```

This creates:

```text
config/laravel-ai-evaluation.php
```
