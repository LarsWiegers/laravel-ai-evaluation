# First eval in 5 minutes

This guide creates one eval, runs it, and shows what happens when it fails.

## 1) Install the package

```bash
composer require --dev larswiegers/laravel-ai-evaluation
php artisan ai-evals:install
```

## 2) Generate an eval

Choose Pest if you already run your test suite with Pest:

```bash
php artisan make:ai-evals refund-policy --type=pest --agent="App\\Ai\\Agents\\SupportAgent"
```

Or choose standalone if you want to run evals without PHPUnit:

```bash
php artisan make:ai-evals refund-policy --type=standalone --agent="App\\Ai\\Agents\\SupportAgent"
```

## 3) Edit the generated eval

The generated file starts with a simple policy check:

```php
use LaravelAIEvaluation\AIEval;

AIEval::agent(App\Ai\Agents\SupportAgent::class)
    ->input('What is your refund policy?')
    ->expectContains(['refund', '30 days'])
    ->run()
    ->assertPasses();
```

Replace the agent class, prompt, and expectations with behavior your app actually needs.

## 4) Configure provider keys

Live evals call real model APIs. Keep keys outside your repository:

```dotenv
OPENAI_API_KEY=your-openai-key
AI_EVAL_RETRIES=1
AI_EVAL_RETRY_SLEEP_MS=250
```

Use the provider key names expected by your Laravel AI setup.

## 5) Run it

For Pest:

```bash
vendor/bin/pest tests/AgentEvals
```

For standalone evals:

```bash
php artisan ai-evals:run
```

## What a failure looks like

If the agent does not mention all expected text, `assertPasses()` fails with this message shape:

```text
AI eval 'refund-policy' failed.
Location: tests/AgentEvals/RefundPolicyEvalTest.php:12
Input: What is your refund policy?
Output: Please contact support for refund questions.
Failures:
- Missing required substring(s): 30 days
```

That failure is the point of the package: prompt, model, tool, or retrieval changes become visible before they reach users.

## Next steps

- Learn the mental model in [Concepts](/concepts).
- Choose a run mode: [Run in Pest](/running-in-pest) or [Run standalone](/running-standalone).
- Add semantic checks with [LLM-as-judge expectations](/llm-as-judge-expectations).
