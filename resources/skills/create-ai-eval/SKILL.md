---
name: create-ai-eval
description: >-
  Use when creating, improving, or running Laravel AI agent evals with
  larswiegers/laravel-ai-evaluation. Guides agents through package setup,
  eval design, Pest or standalone files, expectations, judges, and verification.
author: Lars Wiegers
---

# Create Laravel AI Evals

Use this skill to create high-signal evals for Laravel AI agents with
`larswiegers/laravel-ai-evaluation`.

An eval should answer one product question: "Does this agent still behave the
way the application needs?" It runs a real agent, captures real model output,
and checks that output against explicit expectations.

## When To Use

Use this skill when the user asks to:

- Add eval coverage for a Laravel AI agent.
- Test real model behavior, prompts, retrieval, tools, or agent instructions.
- Convert a known support, billing, sales, scheduling, or policy scenario into an eval.
- Run AI evals in Pest, Artisan, or CI.
- Add deterministic or LLM-as-judge checks for AI responses.

Do not use AI evals as a replacement for normal unit or feature tests. Use unit
tests for deterministic app code, mocked prompt builders, tool classes, database
queries, request validation, and pure PHP behavior.

## Core Workflow

1. Identify the agent class and behavior under test.
2. Choose one or more realistic inputs the agent should handle.
3. Define concrete pass criteria before writing code.
4. Install and configure the package if the project does not already use it.
5. Generate or create the eval file in `tests/AgentEvals`.
6. Replace scaffolded prompts and expectations with domain-specific checks.
7. Run the smallest relevant eval command and fix failures caused by the eval or app behavior.
8. Keep live evals serial and avoid committing provider credentials.

Prefer small, critical, product-oriented evals over broad low-signal suites.
Good eval names include `refund-policy`, `billing-invoice-question`,
`appointment-reschedule`, and `sales-qualification`.

## Package Setup

Check whether `larswiegers/laravel-ai-evaluation` is already installed before
installing it. If it is missing, install it as a dev dependency:

```bash
composer require --dev larswiegers/laravel-ai-evaluation
```

Publish package defaults and create the default eval directory:

```bash
php artisan ai-evals:install
```

The Laravel service provider is auto-discovered.

If Pest evals are used, make sure `tests/Pest.php` discovers `tests/AgentEvals`:

```php
<?php

declare(strict_types=1);

use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature', 'AgentEvals');
```

If the app uses a custom base test case, use that class instead of
`Tests\TestCase`.

## Choose Pest Or Standalone

Use Pest when:

- The project already uses Pest.
- The user wants evals to fail through PHPUnit assertions.
- The eval should live with the regular test suite.

Run Pest evals with:

```bash
vendor/bin/pest tests/AgentEvals
```

Use standalone when:

- The user wants a dedicated eval command.
- The eval suite should run separately from PHPUnit.
- CI should have a serial live-model eval job.

Run standalone evals with:

```bash
php artisan ai-evals:run
```

Run a custom folder or filter with:

```bash
php artisan ai-evals:run tests/AgentEvals/Billing
php artisan ai-evals:run --filter="refund policy"
```

## Generate Eval Files

Use the built-in generator when possible:

```bash
php artisan make:ai-evals refund-policy --type=pest --agent="App\\Ai\\Agents\\SupportAgent"
php artisan make:ai-evals refund-policy --type=standalone --agent="App\\Ai\\Agents\\SupportAgent"
```

Options:

- `--type=pest` creates `tests/AgentEvals/RefundPolicyEvalTest.php`.
- `--type=standalone` creates `tests/AgentEvals/refund-policy.eval.php`.
- `--path=tests/AgentEvals/Billing` writes to a custom relative directory.
- `--force` overwrites an existing generated file.

The generated prompt and expectation are placeholders. Always edit them to
match the application behavior being evaluated.

## Pest Eval Template

Use this shape for Pest evals:

```php
<?php

declare(strict_types=1);

use App\Ai\Agents\SupportAgent;
use LaravelAIEvaluation\AIEval;

it('answers refund policy questions', function () {
    AIEval::agent(SupportAgent::class)
        ->name('refund-policy')
        ->input('Can I get a refund if I bought the plan last week?')
        ->expectContains(['refund', '30 days'])
        ->run()
        ->assertPasses();
});
```

The resolved agent must implement `Laravel\Ai\Contracts\Agent` or expose a
`prompt(string $prompt)` method.

## Standalone Eval Template

Use this shape for standalone evals:

```php
<?php

declare(strict_types=1);

use App\Ai\Agents\SupportAgent;
use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

return static function (StandaloneEvalSuite $suite): void {
    $suite->eval('refund-policy', static function () {
        return AIEval::agent(SupportAgent::class)
            ->input('Can I get a refund if I bought the plan last week?')
            ->expectContains(['refund', '30 days'])
            ->run();
    });
};
```

Standalone files must use the `*.eval.php` filename convention and return a
callable that registers one or more `$suite->eval(...)` cases.

## Designing Good Eval Cases

Start from real product requirements, not generic prompts. For each eval,
capture:

- Agent class under test.
- User input or task prompt.
- Required facts, tokens, policy constraints, or output format.
- Forbidden regressions if relevant.
- Whether exact text, required substrings, or semantic quality is the right check.

Prefer one focused behavior per eval case. Add more cases for meaningfully
different scenarios, not minor wording variations.

Good prompts are realistic and specific:

```php
->input('Can I get a refund if I bought the plan last week?')
```

Weak prompts are vague and make failures hard to interpret:

```php
->input('Tell me about refunds')
```

## Choosing Expectations

Prefer deterministic expectations when they are enough.

Use `expectContains()` when the wording can vary but required facts must appear:

```php
->expectContains('refund')
->expectContains(['refund', '30 days'])
```

All strings must be present and matching is case-sensitive.

Use `expectExact()` when the full response must match exactly after trimming:

```php
->expectExact('OK')
```

Use other deterministic checks when the output has concrete structure or boundaries:

```php
->expectRegex('/refunds? within \d+ days/i')
->expectNotContains(['legal guarantee', 'always approved'])
->expectJson()
->expectJsonPath('status', 'eligible')
->expectLength(max: 500)
->expectStartsWith('{')
->expectEndsWith('}')
```

Use `expect()` for product-specific checks implemented as closures, invokable
classes, or classes implementing `LaravelAIEvaluation\Contracts\EvalExpectation`:

```php
->expect(fn (string $output): bool => str_contains($output, '30 days'))
->expect(App\Ai\Evals\Expectations\RefundPolicyExpectation::class)
```

Use `expectJudge()` when the response quality is semantic and cannot be reduced
to reliable substrings:

```php
->expectJudge(
    criteria: 'The answer should be accurate, concise, polite, and avoid legal overpromising.',
    threshold: 0.8,
)
```

Use `expectJudgeAgainst()` when there is a known-good reference answer:

```php
->expectJudgeAgainst(
    reference: 'Refunds are available within 30 days of purchase.',
    criteria: 'The answer should mention the refund window and stay concise.',
    threshold: 0.8,
)
```

You can combine expectations. Every expectation must pass.

```php
AIEval::agent(SupportAgent::class)
    ->input('Summarize the refund policy in one sentence.')
    ->expectContains(['refund', '30 days'])
    ->expectJudge('The answer should be clear and not longer than one sentence.', threshold: 0.75)
    ->run()
    ->assertPasses();
```

## Judge Configuration

The package ships with `LaravelAIEvaluation\Evaluation\Judge\DefaultJudgeAgent`.

Configure a custom judge in `config/laravel-ai-evaluation.php` when the project
needs a specific provider, model, or judge prompt wrapper:

```php
return [
    'judge' => [
        'agent' => App\Ai\Agents\JudgeAgent::class,
        'threshold' => 0.7,
    ],
];
```

Or set a judge for one eval builder:

```php
->useJudge(App\Ai\Agents\JudgeAgent::class)
```

The judge must be container-resolvable or passed as an object instance. It must
implement `Laravel\Ai\Contracts\Agent` or expose `prompt(string $prompt)`. It
should return JSON only:

```json
{"score":0.82,"reason":"Correct and clear; misses one policy detail."}
```

The score must be numeric between `0` and `1`; any score below the threshold
fails the eval.

## Running And Verifying

For Pest evals:

```bash
vendor/bin/pest tests/AgentEvals
```

For standalone evals:

```bash
php artisan ai-evals:run
```

For local iteration on one standalone case:

```bash
php artisan ai-evals:run --filter="refund-policy"
```

Do not run live evals with Pest parallel workers. Parallel model calls can hit
provider rate limits. If rate limits happen, keep eval runs serial and use
conservative retry settings:

```dotenv
AI_EVAL_RETRIES=2
AI_EVAL_RETRY_SLEEP_MS=500
```

Optional local output settings:

```dotenv
AI_EVAL_VERBOSE=true
AI_EVAL_FORMAT=text
AI_EVAL_SUMMARY=true
AI_EVAL_SUMMARY_FORMAT=text
AI_EVAL_SUMMARY_CURRENCY=USD
```

`AI_EVAL_FORMAT` and `AI_EVAL_SUMMARY_FORMAT` support `text` and `json` for verbose dumps and summaries. Standalone reports also support `text`, `json`, `junit`, and `github` via `php artisan ai-evals:run --format=...`.

## Credentials And Safety

Live evals call real model APIs.

- Never commit provider API keys to eval files, config files, or source control.
- Use `.env` locally and CI secret stores in automation.
- Prefer a dedicated eval key with quota and spend limits.
- Keep PR eval suites small and serial.
- Run broader live eval suites nightly or before releases when cost or latency matters.

Example local environment values:

```dotenv
# Use the provider key names expected by the app's Laravel AI configuration.
OPENAI_API_KEY=your-openai-key
# ANTHROPIC_API_KEY=your-anthropic-key

AI_EVAL_RETRIES=1
AI_EVAL_RETRY_SLEEP_MS=250
```

## Troubleshooting Checklist

If eval discovery fails:

- Confirm Pest includes `AgentEvals` in `tests/Pest.php`.
- Confirm standalone eval files end in `.eval.php`.
- Confirm standalone eval files return a callable accepting `StandaloneEvalSuite`.
- Confirm `config/laravel-ai-evaluation.php` has the expected standalone path.

If an eval has no expectations:

- Add at least one expectation before `run()`, such as a deterministic expectation, `expect()`, `expectJudge()`, or `expectJudgeAgainst()`.

If an agent cannot be resolved:

- Confirm the class namespace and import.
- Confirm the class is container-resolvable or can be instantiated normally.
- Confirm it implements `Laravel\Ai\Contracts\Agent` or exposes `prompt(string $prompt)`.

If judge expectations fail because of invalid judge output:

- Make the judge return plain JSON only.
- Include both `score` and `reason`.
- Keep `score` between `0` and `1`.

If rate limits occur:

- Do not use `vendor/bin/pest --parallel` for live evals.
- Prefer `php artisan ai-evals:run` in a dedicated serial CI job.
- Increase `AI_EVAL_RETRY_SLEEP_MS` to `750` or `1000` if needed.

## Quality Bar

Before finishing, verify the eval is useful:

- The eval name describes a real product behavior.
- The input resembles an actual user request or agent task.
- The expectations would catch a meaningful regression.
- Deterministic checks are used where possible.
- Judge criteria are explicit and product-specific when used.
- The eval can be run with a documented command.
- No credentials or secrets were added.
