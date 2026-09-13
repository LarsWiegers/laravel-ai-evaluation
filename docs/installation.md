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
php artisan make:ai-evals refund-policy
```

Run generated evals with `php artisan ai-evals:run`.

## Optional: publish config

If you want to customize defaults (format, retries, summaries, report safety, eval path, or judge agent), publish the config file:

```bash
php artisan vendor:publish --tag=laravel-ai-evaluation-config
```

This creates:

```text
config/laravel-ai-evaluation.php
```
