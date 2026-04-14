# When to run evals

Agent evals are most useful when output quality matters as much as code correctness.

## Good moments to run them

### On pull requests

Run evals in CI for PRs that change prompts, agent logic, tools, retrieval, or model settings.

Why:

- Catch behavior regressions before merge
- Keep expected quality stable over time
- Make prompt changes reviewable with pass/fail feedback

### Before releases

Run the full `tests/AgentEvals` suite before deployments.

Why:

- Validate critical flows after dependency/model updates
- Confirm outputs still meet product expectations

### During prompt iteration

Run a subset locally while editing prompt logic.

Why:

- Tight feedback loop for faster prompt tuning
- Immediate signal when a change breaks a known behavior

## Suggested cadence

- Small local run while developing: `php artisan ai-evals:run --filter="..."`
- Full suite on PR and on merge to `master`
- Optional scheduled nightly run for broader confidence

## Cases where evals are especially valuable

- Customer support answers (policy, compliance, tone)
- Structured outputs that must include key facts
- Agent workflows where wrong wording creates support or legal risk
- High-impact user journeys (checkout, cancellation, refunds)
