# Laravel AI Evaluation

[![Latest Version on Packagist](https://img.shields.io/packagist/v/LarsWiegers/laravel-ai-evaluation.svg?style=flat-square)](https://packagist.org/packages/LarsWiegers/laravel-ai-evaluation)
[![Total Downloads](https://img.shields.io/packagist/dt/LarsWiegers/laravel-ai-evaluation.svg?style=flat-square)](https://packagist.org/packages/LarsWiegers/laravel-ai-evaluation)
![GitHub Actions](https://github.com/LarsWiegers/laravel-ai-evaluation/actions/workflows/main.yml/badge.svg)
[![Laravel Compatibility](https://badge.laravel.cloud/badge/larswiegers/laravel-ai-evaluation)](https://packagist.org/packages/larswiegers/laravel-ai-evaluation)

![Laravel AI evaluation](https://banners.beyondco.de/Laravel%20AI%20Evaluation.png?theme=light&packageManager=composer+require&packageName=larswiegers%2Flaravel-ai-evaluation&pattern=architect&style=style_1&description=Make+sure+your+agents+respond+how+you+want+them+to.&md=1&showWatermark=0&fontSize=100px&images=beaker)

Test real Laravel AI agent behavior with repeatable evals that run in Pest, Artisan, and CI.

## Quick start

```bash
composer require --dev larswiegers/laravel-ai-evaluation
php artisan make:ai-evals refund-policy --type=pest
vendor/bin/pest tests/AgentEvals
```

```php
use LaravelAIEvaluation\AIEval;

it('answers refund policy questions', function () {
    AIEval::agent(App\Ai\Agents\SupportAgent::class)
        ->input('Can I get a refund?')
        ->expectContains(['refund', '30 days'])
        ->run()
        ->assertPasses();
});
```

For installation and usage instructions, see the docs:

- [Documentation](https://ai-evals.larswiegers.nl/)
