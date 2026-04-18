<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation;

use LaravelAIEvaluation\Standalone\StandaloneEvalContext;

class EvalCaseBuilder
{
    /**
     * @var array<int, string>
     */
    protected array $contains = [];

    protected ?string $exact = null;

    /**
     * @var array<int, array{criteria: string, reference: string|null, threshold: float|null, judge: object|string|null}>
     */
    protected array $judgeExpectations = [];

    protected ?string $name = null;

    protected string $input = '';

    protected object|string|null $judge = null;

    protected ?string $location = null;

    public function __construct(
        protected object|string $agent,
        protected ?EvalRunner $runner = null,
    ) {
        $this->runner = $this->runner ?? (function_exists('app') ? app(EvalRunner::class) : new EvalRunner);
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function input(string $input): self
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @param  string|array<int, string>  $contains
     */
    public function expectContains(string|array $contains): self
    {
        $values = is_array($contains) ? $contains : [$contains];

        foreach ($values as $value) {
            $this->contains[] = $value;
        }

        return $this;
    }

    public function expectExact(string $exact): self
    {
        $this->exact = $exact;

        return $this;
    }

    public function expectJudge(string $criteria, ?float $threshold = null, object|string|null $judge = null): self
    {
        $this->judgeExpectations[] = [
            'criteria' => $criteria,
            'reference' => null,
            'threshold' => $threshold,
            'judge' => $judge ?? $this->judge,
        ];

        return $this;
    }

    public function expectJudgeAgainst(
        string $reference,
        string $criteria,
        ?float $threshold = null,
        object|string|null $judge = null,
    ): self
    {
        $this->judgeExpectations[] = [
            'criteria' => $criteria,
            'reference' => $reference,
            'threshold' => $threshold,
            'judge' => $judge ?? $this->judge,
        ];

        return $this;
    }

    public function run(): EvalResult
    {
        return $this->runner->run(
            agent: $this->agent,
            name: $this->resolveName(),
            input: $this->input,
            contains: $this->contains,
            exact: $this->exact,
            judgeExpectations: $this->judgeExpectations,
            location: $this->location ?? $this->resolveLocation(),
        );
    }

    public function location(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function useJudge(object|string $judge): self
    {
        $this->judge = $judge;

        return $this;
    }

    protected function resolveName(): string
    {
        if ($this->name !== null && $this->name !== '') {
            return $this->name;
        }

        foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 50) as $frame) {
            if (! isset($frame['object']) || ! is_object($frame['object'])) {
                continue;
            }

            $object = $frame['object'];

            if (! class_exists('PHPUnit\\Framework\\TestCase') || ! $object instanceof \PHPUnit\Framework\TestCase) {
                continue;
            }

            if (is_callable([$object, 'getPrintableTestCaseMethodName'])) {
                $name = call_user_func([$object, 'getPrintableTestCaseMethodName']);

                if (is_string($name) && $name !== '') {
                    return $name;
                }
            }

            if (method_exists($object, 'nameWithDataSet')) {
                return $object->nameWithDataSet();
            }

            if (method_exists($object, 'name')) {
                return $object->name();
            }
        }

        $standaloneName = StandaloneEvalContext::currentName();

        if ($standaloneName !== null) {
            return $standaloneName;
        }

        return 'unnamed-eval';
    }

    protected function resolveLocation(): ?string
    {
        $packagePath = str_replace('\\', '/', __DIR__);

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 50) as $frame) {
            if (! isset($frame['file']) || ! is_string($frame['file'])) {
                continue;
            }

            $file = str_replace('\\', '/', $frame['file']);

            if (str_starts_with($file, $packagePath)) {
                continue;
            }

            if (! str_contains($file, '/tests/')) {
                continue;
            }

            $line = isset($frame['line']) && is_int($frame['line']) ? $frame['line'] : 1;

            return sprintf('%s:%d', $frame['file'], $line);
        }

        return null;
    }
}
