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
