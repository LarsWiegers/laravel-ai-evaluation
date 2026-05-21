# Concepts

An eval checks whether an AI agent still behaves the way your product expects.

## Mental model

```text
Agent + input -> real model response -> expectations -> pass/fail result
```

Each eval runs your agent with an input prompt, captures the real response, and scores that response against one or more expectations.

## Evals are not unit tests

Use normal unit and feature tests for deterministic application code. Use AI evals for behavior that depends on prompts, model output, retrieval, tools, or agent instructions.

Good unit test targets:

- Tool classes
- Database queries
- Request validation
- Prompt builders with mocked model calls

Good eval targets:

- Customer-facing answers
- Policy or compliance language
- Structured responses from agents
- Prompt or retrieval changes that can alter output quality

## Deterministic vs judge expectations

Use deterministic expectations when the requirement is concrete:

```php
->expectContains(['refund', '30 days'])
->expectExact('OK')
```

Use judge expectations when quality is semantic:

```php
->expectJudge('The answer should be accurate, concise, and polite.', threshold: 0.8)
```

Prefer deterministic checks when they are enough. Add judge checks when phrasing can vary but quality still matters.

## Run modes

Pest mode is best when you want evals to live beside your test suite and fail through PHPUnit assertions.

Standalone mode is best when you want a dedicated eval command:

```bash
php artisan ai-evals:run
```

Both modes call real agents and can be used in CI.

## Naming evals

Give evals product-oriented names such as `refund-policy`, `billing-invoice-question`, or `appointment-reschedule`. Good names make CI failures easier to understand.

## Keep suites small at first

Start with critical flows. Add more evals as you learn where regressions happen. Live model calls can cost money and hit rate limits, so small high-signal suites are usually better than broad low-signal suites.
