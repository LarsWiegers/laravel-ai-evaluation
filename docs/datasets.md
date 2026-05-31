# Dataset evals

Dataset evals run the same agent checks across multiple rows from a JSON, PHP, or CSV file.

Use them when you want broad coverage without hand-writing one eval case per prompt.

## JSON dataset

Store datasets under `tests/AgentEvals/datasets`:

```json
[
    {
        "name": "refund inside window",
        "input": "I bought this last week. Can I get a refund?",
        "required_terms": ["refund", "30 days"],
        "forbidden_terms": ["guaranteed"]
    }
]
```

Each row should be an object. The optional `name` column is used in failures and standalone reports.

## PHP dataset

PHP datasets work like Pest dataset files: return an array of rows from a PHP file.

```php
<?php

return [
    [
        'name' => 'refund inside window',
        'input' => 'I bought this last week. Can I get a refund?',
        'required_terms' => ['refund', '30 days'],
        'forbidden_terms' => ['guaranteed'],
    ],
];
```

You can also return `['rows' => [...]]` if you want to keep metadata next to the rows.

## CSV dataset

CSV datasets use the first row as column headers:

```csv
name,input,required_term,forbidden_term
refund inside window,I bought this last week. Can I get a refund?,refund,guaranteed
```

CSV cells are strings. Use JSON or PHP datasets when a row needs arrays such as multiple `required_terms`.

## Pest example

```php
use LaravelAIEvaluation\AIEval;

it('answers refund dataset cases', function () {
    AIEval::agent(App\Ai\Agents\SupportAgent::class)
        ->name('refund-policy')
        ->dataset('tests/AgentEvals/datasets/refunds.json')
        ->inputColumn('input')
        ->expectContainsFrom('required_terms')
        ->expectNotContainsFrom('forbidden_terms')
        ->run()
        ->assertPasses();
});
```

`run()` returns a dataset result with one `EvalResult` per row. `assertPasses()` fails if any row fails.

## Standalone example

```php
use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;

return static function (StandaloneEvalSuite $suite): void {
    $suite->eval('refund-policy', static function () {
        return AIEval::agent(App\Ai\Agents\SupportAgent::class)
            ->dataset('tests/AgentEvals/datasets/refunds.json')
            ->inputColumn('input')
            ->expectContainsFrom('required_terms')
            ->expectNotContainsFrom('forbidden_terms')
            ->run();
    });
};
```

The standalone runner expands dataset results into separate report cases, such as `refund-policy / refund inside window`.

## Scaffold a dataset eval

```bash
php artisan make:ai-evals refund-policy --type=standalone --dataset
```

This creates both an eval file and a starter dataset file in `tests/AgentEvals/datasets`.

## Column methods

- `dataset('path/to/file.json')` loads a JSON dataset.
- `dataset('path/to/file.php')` loads a PHP dataset that returns rows.
- `dataset('path/to/file.csv')` loads a CSV dataset with a header row.
- `inputColumn('input')` chooses the row value sent to the agent. The default is `input`.
- `nameColumn('name')` chooses the row label. Pass `null` to use `row 1`, `row 2`, and so on.
- `expectContainsFrom('required_terms')` reads a string or array of required substrings from each row.
- `expectNotContainsFrom('forbidden_terms')` reads a string or array of forbidden substrings from each row.

Column paths support dot notation, such as `expected.required_terms` or `cases.0.input`.
