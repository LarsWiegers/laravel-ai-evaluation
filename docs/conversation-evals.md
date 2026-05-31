# Conversation evals

Conversation evals test an agent against a multi-turn transcript.

The first version flattens the transcript into one prompt, so it works with agents that expose `prompt(string $prompt)`.

## Example

```php
use LaravelAIEvaluation\AIEval;

it('handles refund follow ups', function () {
    AIEval::agent(App\Ai\Agents\SupportAgent::class)
        ->name('refund-follow-up')
        ->conversation()
        ->user('I bought this last week.')
        ->assistantShouldContain('order number')
        ->user('The order is #123.')
        ->expectContains(['refund', '30 days'])
        ->run()
        ->assertPasses();
});
```

## Methods

- `conversation()` starts a conversation eval builder.
- `user('...')` adds a user turn to the transcript.
- `assistantShouldContain(...)` checks the assistant's final response for required text.
- `assistantShouldNotContain(...)` checks the assistant's final response for forbidden text.
- Final-response expectations such as `expectContains()`, `expectNotContains()`, `expectRegex()`, `expectJson()`, and `expectJudge()` are also supported.

## Dataset-backed conversations

Conversation evals can run against JSON, PHP, or CSV datasets.

For JSON and PHP datasets, use a `turns` array when you need multiple prior turns:

```json
[
    {
        "name": "refund follow-up",
        "turns": [
            {"role": "user", "content": "I bought this last week."},
            {"role": "assistant", "content": "Can you share your order number?"},
            {"role": "user", "content": "The order is #123."}
        ],
        "required_terms": ["refund", "30 days"],
        "forbidden_terms": ["guaranteed"]
    }
]
```

```php
AIEval::agent(App\Ai\Agents\SupportAgent::class)
    ->name('refund-conversations')
    ->conversation()
    ->dataset('tests/AgentEvals/datasets/refund-conversations.json')
    ->turnsColumn('turns')
    ->expectContainsFrom('required_terms')
    ->expectNotContainsFrom('forbidden_terms')
    ->run()
    ->assertPasses();
```

CSV datasets can use the default `input` column as a single user turn. Use `inputColumn()` to change that column name.

## Current limitation

Conversation evals currently send the full transcript as one prompt and evaluate the next assistant response. They do not yet call the agent once per assistant turn or use provider-native message arrays.
