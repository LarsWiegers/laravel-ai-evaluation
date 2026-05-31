# Custom expectations

Custom expectations let your app define product-specific checks in PHP. Use them when a rule belongs to your domain, changes faster than this package, or needs app services from the Laravel container.

## Closure expectations

For quick checks, pass a closure to `expect()`.

```php
use LaravelAIEvaluation\AIEval;

AIEval::agent(SupportAgent::class)
    ->input('Can I get a refund?')
    ->expect(fn (string $output): bool => str_contains($output, '30 days'))
    ->run()
    ->assertPasses();
```

Closures receive the output as the first argument. If the closure declares a second argument, it receives the original input.

```php
->expect(function (string $output, string $input): bool {
    return str_contains($input, 'refund') && str_contains($output, '30 days');
})
```

## Reusable expectation classes

For reusable checks, implement `LaravelAIEvaluation\Contracts\EvalExpectation` and return an `ExpectationResult`.

```php
namespace App\Ai\Evals\Expectations;

use LaravelAIEvaluation\Contracts\EvalExpectation;
use LaravelAIEvaluation\Evaluation\ExpectationResult;

class RefundPolicyExpectation implements EvalExpectation
{
    public function evaluate(string $input, string $output): ExpectationResult
    {
        if (str_contains($output, '30 days')) {
            return ExpectationResult::pass(
                reason: 'Output includes the refund window.',
                score: 1.0,
                metadata: ['required_window' => '30 days'],
            );
        }

        return ExpectationResult::fail(
            reason: 'Missing the 30-day refund window.',
            score: 0.0,
            metadata: ['required_window' => '30 days'],
        );
    }
}
```

Then attach it to an eval.

```php
use App\Ai\Evals\Expectations\RefundPolicyExpectation;
use LaravelAIEvaluation\AIEval;

AIEval::agent(SupportAgent::class)
    ->input('Can I get a refund?')
    ->expect(new RefundPolicyExpectation)
    ->run()
    ->assertPasses();
```

## Container-resolved classes

You may pass a class string. The package resolves it through the Laravel container, so constructor dependencies work normally.

```php
AIEval::agent(SupportAgent::class)
    ->input('Can I get a refund?')
    ->expect(RefundPolicyExpectation::class)
    ->run()
    ->assertPasses();
```

Invokable classes are also supported.

```php
class ContainsRefundWindow
{
    public function __invoke(string $output): array
    {
        return [
            'passed' => str_contains($output, '30 days'),
            'reason' => 'Output should include the refund window.',
            'score' => str_contains($output, '30 days') ? 1.0 : 0.0,
            'metadata' => ['required_window' => '30 days'],
        ];
    }
}
```

## Result details

Custom expectation results are included in `EvalResult::expectationResults()` and JSON dumps.

```php
$result = AIEval::agent(SupportAgent::class)
    ->input('Can I get a refund?')
    ->expect(RefundPolicyExpectation::class)
    ->run();

$result->expectationResults();
```

Example result:

```php
[
    [
        'type' => 'custom',
        'name' => App\Ai\Evals\Expectations\RefundPolicyExpectation::class,
        'passed' => false,
        'reason' => 'Missing the 30-day refund window.',
        'score' => 0.0,
        'metadata' => ['required_window' => '30 days'],
    ],
]
```

If a custom expectation throws, the eval records it as a failed custom expectation with the exception class in metadata.
