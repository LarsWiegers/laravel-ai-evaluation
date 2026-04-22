# Create eval files

Use the built-in make command to scaffold eval files for either Pest or standalone runs.

## Basic usage

```bash
php artisan make:ai-evals refund-policy --type=pest
php artisan make:ai-evals refund-policy --type=standalone
```

Generated files:

- Pest: `tests/AgentEvals/RefundPolicyEvalTest.php`
- Standalone: `tests/AgentEvals/refund-policy.eval.php`

## Choose type interactively

If you omit `--type`, the command prompts you to pick `pest` or `standalone`.

```bash
php artisan make:ai-evals refund-policy
```

## Custom output path

Use `--path` to write files to a custom folder:

```bash
php artisan make:ai-evals refund-policy --type=pest --path=tests/AgentEvals/Billing
```

## Custom agent class

Use `--agent` to scaffold a file with your own agent class:

```bash
php artisan make:ai-evals refund-policy --type=pest --agent="App\\Ai\\Agents\\BillingAgent"
```

## Overwrite existing files

If a matching file already exists, generation fails by default. Use `--force` to overwrite it:

```bash
php artisan make:ai-evals refund-policy --type=standalone --force
```

## What template is generated

The generated templates use `AIEval::agent(...)` with a simple `expectContains(['refund', '30 days'])` check so you can run immediately and then adapt to your domain.
