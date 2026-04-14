# Run standalone

If you want to run AI evals without the full test suite, use the built-in Artisan command.

## Run all agent evals

```bash
php artisan ai-evals:run
```

## Run a different folder

```bash
php artisan ai-evals:run tests/SomeOtherFolder
```

## Useful filters

Run one eval file:

```bash
php artisan ai-evals:run --filter="refund policy"
```

By default, the command runs `tests/AgentEvals`.
