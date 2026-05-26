# Run in CI

You can run agent evals in CI with the standalone command.

## GitHub Actions example

```yaml
name: ai-evals

on:
  pull_request:
    branches: [main]
  push:
    branches: [main]

jobs:
  agent-evals:
    runs-on: ubuntu-latest

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          tools: composer:v2

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist --no-progress

      - name: Run AI evals
        env:
          OPENAI_API_KEY: ${{ secrets.OPENAI_API_KEY }}
          AI_EVAL_RETRIES: 1
          AI_EVAL_RETRY_SLEEP_MS: 250
          AI_EVAL_REPORT_INCLUDE_INPUT: false
          AI_EVAL_REPORT_MAX_OUTPUT_LENGTH: 2000
        run: php artisan ai-evals:run --format=github
```

## Upload report artifacts

For CI test report UIs and debugging artifacts, write JUnit and JSON reports to files and upload them.

```yaml
      - name: Run AI evals with reports
        env:
          OPENAI_API_KEY: ${{ secrets.OPENAI_API_KEY }}
          AI_EVAL_REPORT_INCLUDE_INPUT: false
          AI_EVAL_REPORT_MAX_OUTPUT_LENGTH: 2000
        run: |
          php artisan ai-evals:run --format=junit --output=storage/ai-evals/junit.xml
          php artisan ai-evals:run --format=json --output=storage/ai-evals/results.json

      - name: Upload AI eval reports
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: ai-eval-reports
          path: storage/ai-evals
```

Use `--format=github` when you want failures shown inline as GitHub annotations:

```bash
php artisan ai-evals:run --format=github
```

## Report formats

The standalone runner supports four report formats.

For complete examples of each format, see [Output formats](/output-formats).

| Format | Best for | Example |
| --- | --- | --- |
| `text` | Local terminal runs | `php artisan ai-evals:run` |
| `github` | Inline GitHub Actions annotations | `php artisan ai-evals:run --format=github` |
| `junit` | CI test report UIs | `php artisan ai-evals:run --format=junit --output=storage/ai-evals/junit.xml` |
| `json` | Artifacts, dashboards, post-processing | `php artisan ai-evals:run --format=json --output=storage/ai-evals/results.json` |

If you want a browser-readable HTML report from the JUnit file, use a JUnit/XUnit viewer in CI or locally:

```bash
npx xunit-viewer --results=storage/ai-evals/junit.xml --output=storage/ai-evals/junit.html --title="AI Eval Report"
```

## Optional: run only matching cases

```bash
php artisan ai-evals:run --filter="refund"
```

## Recommended strategy

Start with a small high-signal eval suite on pull requests. Run broader eval coverage before releases or on a schedule.

Good PR candidates:

- Prompts or system instructions changed
- Agent tools changed
- Retrieval or knowledge-base logic changed
- Model, provider, or temperature settings changed

For larger suites, use a dedicated scheduled workflow:

```yaml
on:
  schedule:
    - cron: '0 3 * * *'
```

Keep live eval jobs serial unless each job has its own provider key and quota.

## Important notes

- The command exits non-zero on failure, so CI will fail automatically.
- Keep API keys in CI secrets, never in the repository.
- Prefer a dedicated API key for eval jobs (separate from production) with limited quota/budget.
- Keep `AI_EVAL_REPORT_INCLUDE_INPUT=false` unless prompts are safe to publish as CI artifacts.
- Use `AI_EVAL_REPORT_MAX_OUTPUT_LENGTH` and `AI_EVAL_REPORT_MAX_FAILURE_LENGTH` to keep reports concise.
- Keep eval jobs serial to reduce `429` bursts when using a shared provider key.
- Start with a small `tests/AgentEvals` standalone `*.eval.php` set and expand gradually.
- Standalone report formats support `text`, `json`, `junit`, and `github`; see [Output formats](/output-formats) for examples.
- If CI hits `429`/rate limits, follow the dedicated guide: [Dealing with rate limits](/dealing-with-rate-limits).
