# laravel-ai-evaluation

[![Latest Version on Packagist](https://img.shields.io/packagist/v/LarsWiegers/laravel-ai-evaluation.svg?style=flat-square)](https://packagist.org/packages/LarsWiegers/laravel-ai-evaluation)
[![Total Downloads](https://img.shields.io/packagist/dt/LarsWiegers/laravel-ai-evaluation.svg?style=flat-square)](https://packagist.org/packages/LarsWiegers/laravel-ai-evaluation)
![GitHub Actions](https://github.com/LarsWiegers/laravel-ai-evaluation/actions/workflows/main.yml/badge.svg)

Laravel AI Evaluation helps you run LLM output evaluations directly in your test suite using real model calls.

## Installation

You can install the package via composer:

```bash
composer require larswiegers/laravel-ai-evaluation
```

## Usage

```php
use App\Ai\Agents\SupportAgent;
use LaravelAIEvaluation\AIEval;

AIEval::agent(SupportAgent::class)
    ->name('refund-policy')
    ->input('What is your refund policy?')
    ->expectContains(['refund', '30 days'])
    ->run()
    ->assertPasses();
```

You can also assert exact outputs:

```php
AIEval::agent(SupportAgent::class)
    ->name('healthcheck')
    ->input('Reply with exactly: OK')
    ->expectExact('OK')
    ->run()
    ->assertPasses();
```

And evaluate with an LLM judge rubric + reference answer:

```php
AIEval::agent(SupportAgent::class)
    ->input('What is your refund policy?')
    ->expectJudgeAgainst(
        reference: 'Refunds are available within 30 days of purchase.',
        criteria: 'The answer should be correct, concise, and mention the 30 day window.',
        threshold: 0.8,
        judge: App\Ai\Agents\JudgeAgent::class,
    )
    ->run()
    ->assertPasses();
```

You can configure one judge for the whole eval chain:

```php
AIEval::agent(SupportAgent::class)
    ->input('What is your refund policy?')
    ->useJudge(App\Ai\Agents\JudgeAgent::class)
    ->expectJudge('The answer should be concise and mention the refund window.', threshold: 0.8)
    ->expectJudgeAgainst(
        reference: 'Refunds are available within 30 days of purchase.',
        criteria: 'The answer should be correct and complete.',
        threshold: 0.8,
    )
    ->run()
    ->assertPasses();
```

The package includes a default judge agent, so you can start immediately if Laravel AI is available.
You can still override the default in config or pass one per expectation as shown above.

### Debug output and formats

`EvalResult` supports `dump()` and `dd()` in `text` and `json` formats:

```php
$result = AIEval::agent(SupportAgent::class)
    ->input('What is your refund policy?')
    ->expectContains(['refund'])
    ->run();

$result->dump(); // text
$result->dump(format: 'json'); // JSON line
```

Verbose mode and default output format are configurable:

```env
AI_EVAL_VERBOSE=true
AI_EVAL_FORMAT=text
```

Optional retry settings help reduce flaky failures from transient provider issues:

```env
AI_EVAL_RETRIES=1
AI_EVAL_RETRY_SLEEP_MS=250
```

Run summaries (passed / failed / token usage / cost) are also configurable:

```env
AI_EVAL_SUMMARY=true
AI_EVAL_SUMMARY_FORMAT=json
AI_EVAL_SUMMARY_CURRENCY=USD
```

Recommended location for standalone eval files is `tests/AgentEvals` using `*.eval.php` filenames.

### Standalone runner (no test framework required)

Run evals directly:

```bash
php artisan ai-evals:run
```

You can also target a custom path and optional name filter:

```bash
php artisan ai-evals:run tests/AgentEvals --filter=refund
```

Create files that return a callable receiving `\LaravelAIEvaluation\Standalone\StandaloneEvalSuite`:

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

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email larswiegers@live.nl instead of using the issue tracker.

## Credits

-   [Lars Wiegers](https://github.com/Lars Wiegers)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
