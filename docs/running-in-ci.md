# Run in CI

You can run agent evals in CI with the standalone command.

## GitHub Actions example

```yaml
name: ai-evals

on:
  pull_request:
    branches: [master]
  push:
    branches: [master]

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
        run: php artisan ai-evals:run
```

## Optional: run only matching cases

```bash
php artisan ai-evals:run --filter="refund"
```

## Important notes

- The command exits non-zero on failure, so CI will fail automatically.
- Keep API keys in CI secrets, never in the repository.
- Start with a small `tests/AgentEvals` suite and expand gradually.
