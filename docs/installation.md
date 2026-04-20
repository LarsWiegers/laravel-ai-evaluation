# Installation

Install the package with Composer:

```bash
composer require --dev larswiegers/laravel-ai-evaluation
```

The service provider is auto-discovered by Laravel.

## Install package defaults

Publish config and ensure the default eval directory exists:

```bash
php artisan ai-evals:install
```

## Next step

Generate your first eval file:

```bash
php artisan ai-evals:make refund-policy --type=pest
```

Or standalone:

```bash
php artisan ai-evals:make refund-policy --type=standalone
```

## Default test case (Pest)

Make sure your `tests/Pest.php` includes `AgentEvals` so Pest discovers generated eval tests:

```php
<?php

declare(strict_types=1);

use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature', 'AgentEvals');
```

If you use a custom base test case, replace `Tests\TestCase` with your project test case class.

## Optional: publish config

If you want to customize defaults (format, retries, summary, judge agent), publish the config file:

```bash
php artisan vendor:publish --tag=laravel-ai-evaluation-config
```

This creates:

```text
config/laravel-ai-evaluation.php
```
