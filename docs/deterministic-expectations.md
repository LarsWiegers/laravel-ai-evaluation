# Deterministic expectations

Deterministic expectations define clear pass/fail checks with no model-judge scoring.

## `expectContains`

Use `expectContains` when output can vary, but must include key facts.

```php
use LaravelAIEvaluation\AIEval;

AIEval::agent(SupportAgent::class)
    ->input('What is your refund policy?')
    ->expectContains(['refund', '30 days'])
    ->run()
    ->assertPasses();
```

Behavior:

- Accepts a string or array of strings
- All provided strings must be present in the response
- Matching is case-sensitive

## `expectExact`

Use `expectExact` when output must match exactly.

```php
use LaravelAIEvaluation\AIEval;

AIEval::agent(HealthcheckAgent::class)
    ->input('Reply with exactly: OK')
    ->expectExact('OK')
    ->run()
    ->assertPasses();
```

Behavior:

- Compares full output text
- Applies `trim()` to both expected and actual output before comparison
- Matching is case-sensitive

## Combining expectations

You can use both expectations in one eval.

```php
use LaravelAIEvaluation\AIEval;

AIEval::agent(SupportAgent::class)
    ->input('Summarize refund terms in one sentence')
    ->expectContains('refund')
    ->expectExact('Refunds are available within 30 days of purchase.')
    ->run()
    ->assertPasses();
```

If either expectation fails, the eval fails.

## Failure output

When a required substring is missing, the failure explains which requirement was not met:

```text
Missing required substring(s): 30 days
```

When exact matching fails, the failure includes both values:

```text
Expected exact output "OK" but received "Okay"
```
