# API reference

This page summarizes the fluent eval API.

## `AIEval::agent()`

Creates an eval builder for an agent class string or object instance.

```php
AIEval::agent(App\Ai\Agents\SupportAgent::class)
```

The resolved agent must implement `Laravel\Ai\Contracts\Agent` or expose `prompt(string $prompt)`.

## `name()`

Sets the eval name shown in output and failure messages.

```php
->name('refund-policy')
```

If omitted, the package tries to infer the Pest test name or standalone suite name.

## `input()`

Sets the prompt sent to the agent.

```php
->input('What is your refund policy?')
```

## `expectContains()`

Requires one or more substrings to appear in the agent output.

```php
->expectContains('refund')
->expectContains(['refund', '30 days'])
```

All provided strings must be present. Matching is case-sensitive.

## `expectExact()`

Requires the full output to match exactly after trimming both values.

```php
->expectExact('OK')
```

## `expectRegex()`

Requires the output to match a regular expression.

```php
->expectRegex('/refunds? within \d+ days/i')
```

## `expectNotContains()`

Requires one or more substrings to be absent from the agent output.

```php
->expectNotContains('always approved')
->expectNotContains(['legal guarantee', 'always approved'])
```

Matching is case-sensitive.

## `expectJson()`

Requires the output to be valid JSON.

```php
->expectJson()
```

## `expectJsonPath()`

Requires a JSON path to exist, or to equal an expected value when provided.

```php
->expectJsonPath('status')
->expectJsonPath('status', 'eligible')
->expectJsonPath('policy.days', 30)
```

Paths use dot notation and may include array indexes, such as `items.0.name`.

## `expectLength()`

Requires the output length to be within the provided bounds.

```php
->expectLength(min: 20)
->expectLength(max: 500)
->expectLength(min: 20, max: 500)
```

## `expectStartsWith()`

Requires the output to start with a string.

```php
->expectStartsWith('{')
```

## `expectEndsWith()`

Requires the output to end with a string.

```php
->expectEndsWith('}')
```

## `expectJudge()`

Scores the output with an LLM judge using criteria and an optional threshold.

```php
->expectJudge(
    criteria: 'The answer should be accurate, concise, and polite.',
    threshold: 0.8,
)
```

## `expectJudgeAgainst()`

Scores the output with an LLM judge using criteria plus a reference answer.

```php
->expectJudgeAgainst(
    reference: 'Refunds are available within 30 days of purchase.',
    criteria: 'The answer should mention the refund window.',
    threshold: 0.8,
)
```

## `useJudge()`

Sets a judge agent for all judge expectations on the builder.

```php
->useJudge(App\Ai\Agents\JudgeAgent::class)
```

You can also pass `judge:` directly to `expectJudge()` or `expectJudgeAgainst()`.

## `run()`

Runs the eval and returns an `EvalResult`.

```php
$result = AIEval::agent(SupportAgent::class)
    ->input('What is your refund policy?')
    ->expectContains('refund')
    ->run();
```

At least one expectation is required.

## `assertPasses()`

Fails the current Pest/PHPUnit test, or throws a runtime exception outside PHPUnit, if the eval failed.

```php
->run()
->assertPasses();
```

## `dump()`

Writes eval details in `text` or `json` format.

```php
->run()
->dump(format: 'json');
```

## `EvalResult`

Useful methods on the result object:

- `passed()` returns `true` when every expectation passed.
- `failures()` returns failure messages.
- `output()` returns the normalized agent output.
- `expectationResults()` returns details for each expectation.
- `usage()` returns token and cost usage when the provider response exposes it.
