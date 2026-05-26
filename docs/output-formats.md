# Output formats

The standalone runner can write reports in four formats: `text`, `json`, `junit`, and `github`.

Use `--format` to choose a format and `--output` to write it to a file.

```bash
php artisan ai-evals:run --format=json --output=storage/ai-evals/results.json
```

## `text`

Use `text` for local development and quick terminal feedback. This is the default.

```bash
php artisan ai-evals:run
php artisan ai-evals:run --format=text
```

Example output:

```text
PASS refund-policy
FAIL unsafe-refund-policy
  - Missing required substring(s): 30 days
  - Output contained forbidden substring(s): always approved

Standalone eval summary: total=2 passed=1 failed=1
```

## `json`

Use `json` for artifacts, dashboards, post-processing, and debugging. It includes the run summary plus per-case details.

```bash
php artisan ai-evals:run --format=json --output=storage/ai-evals/results.json
```

Example output:

```json
{
  "type": "ai_eval_standalone_report",
  "total": 2,
  "passed": 1,
  "failed": 1,
  "errors": 0,
  "cases": [
    {
      "name": "unsafe-refund-policy",
      "location": "tests/AgentEvals/refund.eval.php:35",
      "passed": false,
      "failures": [
        "Missing required substring(s): 30 days"
      ],
      "input": null,
      "output": "Refunds are always approved with no review.",
      "expectation_results": [
        {
          "type": "contains",
          "passed": false,
          "reason": "Missing required substring(s): 30 days"
        }
      ]
    }
  ]
}
```

## `junit`

Use `junit` for CI test report UIs. GitHub Actions, GitLab, Jenkins, Azure DevOps, and many other tools understand JUnit XML.

```bash
php artisan ai-evals:run --format=junit --output=storage/ai-evals/junit.xml
```

Example output:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<testsuites tests="2" failures="1" errors="0">
  <testsuite name="AI Evals" tests="2" failures="1" errors="0">
    <testcase name="refund-policy" classname="AI Evals" file="tests/AgentEvals/refund.eval.php" line="17"/>
    <testcase name="unsafe-refund-policy" classname="AI Evals" file="tests/AgentEvals/refund.eval.php" line="35">
      <failure message="Missing required substring(s): 30 days">Missing required substring(s): 30 days</failure>
    </testcase>
  </testsuite>
</testsuites>
```

To view JUnit locally in a browser, convert it to HTML with a viewer such as `xunit-viewer`:

```bash
npx xunit-viewer --results=storage/ai-evals/junit.xml --output=storage/ai-evals/junit.html --title="AI Eval Report"
```

## `github`

Use `github` inside GitHub Actions when you want failed evals to appear as inline annotations in the workflow UI.

```bash
php artisan ai-evals:run --format=github
```

Example output:

```text
::error file=tests/AgentEvals/refund.eval.php,line=35,title=AI eval failed%3A unsafe-refund-policy::Missing required substring(s): 30 days
```

## Safety controls

Reports can contain model outputs, failure messages, expectation metadata, and optionally prompts. Configure truncation and omission before uploading reports as CI artifacts.

```dotenv
AI_EVAL_REPORT_INCLUDE_INPUT=false
AI_EVAL_REPORT_INCLUDE_OUTPUT=true
AI_EVAL_REPORT_MAX_INPUT_LENGTH=500
AI_EVAL_REPORT_MAX_OUTPUT_LENGTH=2000
AI_EVAL_REPORT_MAX_FAILURE_LENGTH=1000
```

Inputs are omitted by default. Outputs are included by default and are passed through redaction patterns from `config/laravel-ai-evaluation.php`.
