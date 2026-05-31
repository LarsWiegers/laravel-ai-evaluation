# Expectations

Expectations define how an eval case passes or fails.

## Available categories

- [Deterministic expectations](./deterministic-expectations)
- [LLM-as-judge expectations](./llm-as-judge-expectations)
- [Custom expectations](./custom-expectations)
- [Dataset evals](./datasets)
- [Conversation evals](./conversation-evals)

Tool call assertions such as `expectToolCalled`, `expectToolCalledWith`, and `expectToolNotCalled` are documented with deterministic expectations because they evaluate the recorded execution trace without another model call.

More expectation types can be added over time.

For method signatures and return values, see the [API reference](/api-reference).
