# Dealing with rate limits

If your eval runs fail with errors like `429`, `rate limit`, or `too many requests`, the provider is rejecting request bursts.

This guide helps you make eval runs stable in local development and CI.

## Why this happens

Live evals call real model APIs. If too many calls happen in a short window, providers throttle requests.

Common causes:

- Running evals in parallel test workers
- Running multiple CI jobs against the same API key at the same time
- Using retries with no pause between attempts

## Recommended baseline

Use standalone eval runs for live model checks and keep them serial:

```bash
php artisan ai-evals:run
```

Then add conservative retry settings:

```env
AI_EVAL_RETRIES=2
AI_EVAL_RETRY_SLEEP_MS=500
```

If `429` responses still happen, increase `AI_EVAL_RETRY_SLEEP_MS` to `750` or `1000`.

## CI setup that usually works

Use a dedicated serial job for evals:

```yaml
- name: Run AI evals (serial)
  env:
    OPENAI_API_KEY: ${{ secrets.OPENAI_API_KEY }}
    AI_EVAL_RETRIES: 2
    AI_EVAL_RETRY_SLEEP_MS: 500
    AI_EVAL_SUMMARY: true
  run: php artisan ai-evals:run
```

Also avoid matrix fan-out for live eval jobs unless each job has its own provider key and quota.

## If you run evals in Pest

Prefer a non-parallel run for eval tests:

```bash
vendor/bin/pest tests/AgentEvals
```

Avoid `--parallel` for live eval suites.

## Fast troubleshooting checklist

- Confirm failures include `429` or rate-limit wording
- Verify live eval suites are not parallelized
- Increase `AI_EVAL_RETRY_SLEEP_MS`
- Reduce suite size with `--filter` during local iteration
- Run full suites in CI only when needed (PRs touching prompts/agents, pre-release, or nightly)

## Practical strategy for teams

- Keep deterministic tests (unit/feature with mocks) fast and parallel
- Keep live evals small on PRs and serial
- Run broader live eval coverage on schedule (for example nightly)

This split keeps developer feedback fast while making live eval quality checks reliable.
