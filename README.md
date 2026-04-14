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
use LaravelAIEvaluation\LaravelAIEvaluation\LaravelAIEvaluationFacade as AiEval;

AiEval::agent(SupportAgent::class)
    ->case('refund-policy')
    ->input('What is your refund policy?')
    ->expectContains(['refund', '30 days'])
    ->run()
    ->assertPasses();
```

You can also assert exact outputs:

```php
AiEval::agent(SupportAgent::class)
    ->case('healthcheck')
    ->input('Reply with exactly: OK')
    ->expectExact('OK')
    ->run()
    ->assertPasses();
```

And evaluate with an LLM judge rubric + reference answer:

```php
AiEval::agent(SupportAgent::class)
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

The package includes a default judge agent, so you can start immediately if Laravel AI is available.
You can still override the default in config or pass one per expectation as shown above.

Recommended location for these eval tests is `tests/AgentEvals`.

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email test@test.com instead of using the issue tracker.

## Credits

-   [Lars Wiegers](https://github.com/Lars Wiegers)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
