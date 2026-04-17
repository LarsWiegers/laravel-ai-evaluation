<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\LaravelAIEvaluation\Evaluation;

class EvalRunSummary
{
    protected static bool $booted = false;

    protected static bool $shutdownRegistered = false;

    protected static bool $enabled = false;

    protected static string $format = 'text';

    protected static string $currency = 'USD';

    protected static int $total = 0;

    protected static int $passed = 0;

    protected static int $failed = 0;

    /**
     * @var array{prompt_tokens: int, completion_tokens: int, total_tokens: int, cost: float}
     */
    protected static array $usageTotals = [
        'prompt_tokens' => 0,
        'completion_tokens' => 0,
        'total_tokens' => 0,
        'cost' => 0.0,
    ];

    public static function record(EvalResult $result): void
    {
        self::boot();

        if (! self::$enabled) {
            return;
        }

        self::$total++;

        if ($result->passed()) {
            self::$passed++;
        } else {
            self::$failed++;
        }

        $usage = $result->usage();
        self::$usageTotals['prompt_tokens'] += (int) ($usage['prompt_tokens'] ?? 0);
        self::$usageTotals['completion_tokens'] += (int) ($usage['completion_tokens'] ?? 0);
        self::$usageTotals['total_tokens'] += (int) ($usage['total_tokens'] ?? 0);
        self::$usageTotals['cost'] += (float) ($usage['cost'] ?? 0.0);
    }

    public static function flush(?callable $writer = null): void
    {
        self::boot();

        if (! self::$enabled || self::$total === 0) {
            return;
        }

        $writer = $writer ?? static function (string $line): void {
            fwrite(STDOUT, $line.PHP_EOL);
        };

        if (self::$format === 'json') {
            $payload = json_encode(self::toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if (is_string($payload)) {
                $writer($payload);
            }

            return;
        }

        $writer('AI Eval Summary');
        $writer(sprintf('Total: %d', self::$total));
        $writer(sprintf('Passed: %d', self::$passed));
        $writer(sprintf('Failed: %d', self::$failed));
        $writer(sprintf('Prompt tokens: %d', self::$usageTotals['prompt_tokens']));
        $writer(sprintf('Completion tokens: %d', self::$usageTotals['completion_tokens']));
        $writer(sprintf('Total tokens: %d', self::$usageTotals['total_tokens']));
        $writer(sprintf('Estimated cost: %s %.6f', self::$currency, self::$usageTotals['cost']));
    }

    /**
     * @return array<string, int|float|string>
     */
    public static function toArray(): array
    {
        return [
            'type' => 'ai_eval_summary',
            'total' => self::$total,
            'passed' => self::$passed,
            'failed' => self::$failed,
            'prompt_tokens' => self::$usageTotals['prompt_tokens'],
            'completion_tokens' => self::$usageTotals['completion_tokens'],
            'total_tokens' => self::$usageTotals['total_tokens'],
            'estimated_cost' => round(self::$usageTotals['cost'], 6),
            'currency' => self::$currency,
        ];
    }

    public static function resetForTests(): void
    {
        self::$booted = false;
        self::$enabled = false;
        self::$format = 'text';
        self::$currency = 'USD';
        self::$total = 0;
        self::$passed = 0;
        self::$failed = 0;
        self::$usageTotals = [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
            'cost' => 0.0,
        ];
    }

    protected static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$enabled = (bool) config('laravel-ai-evaluation.summary.enabled', false);
        self::$format = (string) config('laravel-ai-evaluation.summary.format', 'text');
        self::$currency = strtoupper((string) config('laravel-ai-evaluation.summary.currency', 'USD'));

        if (! self::$shutdownRegistered) {
            register_shutdown_function(static function (): void {
                self::flush();
            });

            self::$shutdownRegistered = true;
        }

        self::$booted = true;
    }
}
