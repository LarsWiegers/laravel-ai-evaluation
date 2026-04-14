<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\LaravelAIEvaluation\Evaluation;

class EvalCaseBuilder
{
    /**
     * @var array<int, string>
     */
    protected array $contains = [];

    protected ?string $exact = null;

    protected ?string $caseId = null;

    protected string $input = '';

    public function __construct(
        protected object|string $agent,
        protected ?EvalRunner $runner = null,
    ) {
        $this->runner = $this->runner ?? new EvalRunner;
    }

    public function case(string $caseId): self
    {
        $this->caseId = $caseId;

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

    public function run(): EvalResult
    {
        return $this->runner->run(
            agent: $this->agent,
            caseId: $this->resolveCaseId(),
            input: $this->input,
            contains: $this->contains,
            exact: $this->exact,
        );
    }

    protected function resolveCaseId(): string
    {
        if ($this->caseId !== null && $this->caseId !== '') {
            return $this->caseId;
        }

        foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT) as $frame) {
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

        return 'unnamed-case';
    }
}
