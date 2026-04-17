# Run in Pest

This is the recommended way to run evaluations.

## 1) Put eval tests in `tests/AgentEvals`

Example `tests/AgentEvals/SupportAgentEvalTest.php`:

```php
<?php

declare(strict_types=1);

use App\Ai\Agents\SupportAgent;
use LaravelAIEvaluation\LaravelAIEvaluation\AIEval;

it('returns refund policy details', function () {
    AIEval::agent(SupportAgent::class)
        ->case('refund-policy')
        ->input('What is your refund policy?')
        ->expectContains(['refund', '30 days'])
        ->run()
        ->assertPasses();
});
```

## 2) Ensure Pest discovers this folder

In `tests/Pest.php`:

```php
pest()->extend(Tests\TestCase::class)->in('Feature', 'AgentEvals');
```

## 3) Run Pest

```bash
vendor/bin/pest
```

When an eval fails, the test fails with a PHPUnit assertion message from `assertPasses()`.

## Optional: verbose output during Pest runs

```env
AI_EVAL_VERBOSE=true
AI_EVAL_FORMAT=text
```

`AI_EVAL_FORMAT` supports `text` and `json`.
