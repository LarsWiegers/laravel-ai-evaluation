# Deterministic expectations

Deterministic expectations define clear pass/fail checks with no model-judge scoring.

Use them when the requirement can be checked mechanically: required or forbidden text, exact structure, JSON validity, JSON fields, response length, or fixed prefixes and suffixes. They are faster, cheaper, and less flaky than LLM-as-judge expectations because they do not make another model call.

Reach for `expectJudge()` or `expectJudgeAgainst()` when the requirement needs semantic judgment, such as factual equivalence, tone, completeness, or whether a nuanced policy was followed.

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

## `expectRegex`

Use `expectRegex` when output must match a pattern, but exact wording can vary.

```php
use LaravelAIEvaluation\AIEval;

AIEval::agent(SupportAgent::class)
    ->input('Summarize the refund policy.')
    ->expectRegex('/refunds? within \d+ days/i')
    ->run()
    ->assertPasses();
```

## `expectNotContains`

Use `expectNotContains` to block forbidden wording.

```php
AIEval::agent(SupportAgent::class)
    ->input('Summarize refund eligibility.')
    ->expectNotContains(['legal guarantee', 'always approved'])
    ->run()
    ->assertPasses();
```

Behavior:

- Accepts a string or array of strings
- Fails when any provided string is present
- Matching is case-sensitive

## `expectJson`

Use `expectJson` when the agent must return parseable JSON.

```php
AIEval::agent(SupportAgent::class)
    ->input('Summarize the refund policy as JSON.')
    ->expectJson()
    ->run()
    ->assertPasses();
```

## `expectJsonPath`

Use `expectJsonPath` when JSON output must include a field or match a specific value.

```php
AIEval::agent(SupportAgent::class)
    ->input('Summarize the refund policy as JSON.')
    ->expectJsonPath('status', 'eligible')
    ->expectJsonPath('policy.days', 30)
    ->run()
    ->assertPasses();
```

Behavior:

- Uses dot notation such as `status`, `policy.days`, or `items.0.name`
- Accepts an optional `$.` prefix, such as `$.policy.days`
- If no expected value is provided, only checks that the path exists
- If an expected value is provided, compares it strictly

## `expectLength`

Use `expectLength` to constrain response size.

```php
AIEval::agent(SupportAgent::class)
    ->input('Summarize the refund policy in one short paragraph.')
    ->expectLength(max: 500)
    ->run()
    ->assertPasses();
```

You may provide `min`, `max`, or both.

## `expectStartsWith` and `expectEndsWith`

Use these checks when the output must have fixed boundaries.

```php
AIEval::agent(JsonAgent::class)
    ->input('Return only a JSON object.')
    ->expectStartsWith('{')
    ->expectEndsWith('}')
    ->run()
    ->assertPasses();
```

## Combining expectations

You can combine deterministic expectations in one eval.

```php
use LaravelAIEvaluation\AIEval;

AIEval::agent(SupportAgent::class)
    ->input('Summarize the refund policy as JSON.')
    ->expectRegex('/refunds? within \d+ days/i')
    ->expectNotContains(['legal guarantee', 'always approved'])
    ->expectJson()
    ->expectJsonPath('status', 'eligible')
    ->expectLength(max: 500)
    ->run()
    ->assertPasses();
```

If any expectation fails, the eval fails.

## Failure output

When a required substring is missing, the failure explains which requirement was not met:

```text
Missing required substring(s): 30 days
```

When exact matching fails, the failure includes both values:

```text
Expected exact output "OK" but received "Okay"
```

Other deterministic failures identify the violated rule:

```text
Output contained forbidden substring(s): always approved
Output is not valid JSON: Syntax error
Expected JSON path "status" to equal "eligible", but received "ineligible".
Expected output length to be at most 500 characters, but received 812 characters.
```
