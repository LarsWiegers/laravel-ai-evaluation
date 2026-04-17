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

## Output and summary options

The standalone runner supports verbose eval output format configuration:

```env
AI_EVAL_VERBOSE=true
AI_EVAL_FORMAT=json
```

Supported formats are `text` and `json`.

The standalone command also enables end-of-run summaries by default in the spawned Pest process.
You can override summary settings with:

```env
AI_EVAL_SUMMARY=true
AI_EVAL_SUMMARY_FORMAT=text
AI_EVAL_SUMMARY_CURRENCY=USD
```
