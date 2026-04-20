<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation;

class EvalRunSummary
{
    protected bool $shutdownRegistered = false;

    protected bool $flushed = false;

    protected bool $enabled = false;

    protected string $format = 'text';

    protected string $currency = 'USD';

    protected int $total = 0;

    protected int $passed = 0;

    protected int $failed = 0;

    /**
     * @var array{prompt_tokens: int, completion_tokens: int, total_tokens: int, cost: float}
     */
    protected array $usageTotals = [
        'prompt_tokens' => 0,
        'completion_tokens' => 0,
        'total_tokens' => 0,
        'cost' => 0.0,
    ];

    public function record(EvalResult $result): void
    {
        $this->boot();

        if (! $this->enabled) {
            return;
        }

        $this->total++;

        if ($result->passed()) {
            $this->passed++;
        } else {
            $this->failed++;
        }

        $usage = $result->usage();
        $this->usageTotals['prompt_tokens'] += (int) ($usage['prompt_tokens'] ?? 0);
        $this->usageTotals['completion_tokens'] += (int) ($usage['completion_tokens'] ?? 0);
        $this->usageTotals['total_tokens'] += (int) ($usage['total_tokens'] ?? 0);
        $this->usageTotals['cost'] += (float) ($usage['cost'] ?? 0.0);

        $this->flushed = false;
    }

    public function flush(?callable $writer = null): void
    {
        $this->boot();

        if (! $this->enabled || $this->total === 0 || $this->flushed) {
            return;
        }

        $writer = $writer ?? static function (string $line): void {
            fwrite(STDOUT, $line.PHP_EOL);
        };

        if ($this->format === 'json') {
            $payload = json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if (is_string($payload)) {
                $writer($payload);
            }

            $this->flushed = true;

            return;
        }

        $writer('AI Eval Summary');
        $writer(sprintf('Total: %d', $this->total));
        $writer(sprintf('Passed: %d', $this->passed));
        $writer(sprintf('Failed: %d', $this->failed));
        $writer(sprintf('Prompt tokens: %d', $this->usageTotals['prompt_tokens']));
        $writer(sprintf('Completion tokens: %d', $this->usageTotals['completion_tokens']));
        $writer(sprintf('Total tokens: %d', $this->usageTotals['total_tokens']));
        $writer(sprintf('Estimated cost: %s %.6f', $this->currency, $this->usageTotals['cost']));

        $this->flushed = true;
    }

    /**
     * @return array<string, int|float|string>
     */
    public function toArray(): array
    {
        return [
            'type' => 'ai_eval_summary',
            'total' => $this->total,
            'passed' => $this->passed,
            'failed' => $this->failed,
            'prompt_tokens' => $this->usageTotals['prompt_tokens'],
            'completion_tokens' => $this->usageTotals['completion_tokens'],
            'total_tokens' => $this->usageTotals['total_tokens'],
            'estimated_cost' => round($this->usageTotals['cost'], 6),
            'currency' => $this->currency,
        ];
    }

    public function resetForTests(): void
    {
        $this->enabled = false;
        $this->format = 'text';
        $this->currency = 'USD';
        $this->total = 0;
        $this->passed = 0;
        $this->failed = 0;
        $this->usageTotals = [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
            'cost' => 0.0,
        ];
        $this->flushed = false;
    }

    protected function boot(): void
    {
        $this->enabled = (bool) config('laravel-ai-evaluation.summary.enabled', false);
        $this->format = (string) config('laravel-ai-evaluation.summary.format', 'text');
        $this->currency = strtoupper((string) config('laravel-ai-evaluation.summary.currency', 'USD'));

        if ($this->enabled && ! $this->shutdownRegistered && ! $this->runningUnitTests()) {
            register_shutdown_function(function (): void {
                $this->flush();
            });

            $this->shutdownRegistered = true;
        }
    }

    protected function runningUnitTests(): bool
    {
        if (! function_exists('app')) {
            return false;
        }

        try {
            return app()->runningUnitTests();
        } catch (\Throwable) {
            return false;
        }
    }
}
