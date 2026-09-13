# Create eval files

Use the built-in make command to scaffold eval files for the Artisan runner.

## Basic usage

```bash
php artisan make:ai-evals refund-policy
```

Generated files:

- `tests/AgentEvals/refund-policy.eval.php`

## Custom output path

Use `--path` to write files to a custom folder:

```bash
php artisan make:ai-evals refund-policy --path=tests/AgentEvals/Billing
```

## Custom agent class

Use `--agent` to scaffold a file with your own agent class:

```bash
php artisan make:ai-evals refund-policy --agent="App\\Ai\\Agents\\BillingAgent"
```

## Overwrite existing files

If a matching file already exists, generation fails by default. Use `--force` to overwrite it:

```bash
php artisan make:ai-evals refund-policy --force
```

## What template is generated

The generated templates use `AIEval::agent(...)` with a simple `expectContains(['refund', '30 days'])` check so you can run immediately and then adapt to your domain.

## Dataset-backed evals

Use `--dataset` to scaffold an eval file plus a sample JSON dataset:

```bash
php artisan make:ai-evals refund-policy --dataset
```

Generated files:

- `tests/AgentEvals/refund-policy.eval.php`
- `tests/AgentEvals/datasets/refund-policy.json`

The generated eval uses `dataset()`, `inputColumn()`, `expectContainsFrom()`, and `expectNotContainsFrom()`.
