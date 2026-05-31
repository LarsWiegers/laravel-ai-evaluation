<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation;

use InvalidArgumentException;
use LaravelAIEvaluation\Standalone\StandaloneEvalContext;
use RuntimeException;

class EvalCaseBuilder
{
    /**
     * @var array<int, string>
     */
    protected array $contains = [];

    protected ?string $exact = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $deterministicExpectations = [];

    /**
     * @var array<int, callable|object|string>
     */
    protected array $customExpectations = [];

    /**
     * @var array<int, array{criteria: string, reference: string|null, threshold: float|null, judge: object|string|null}>
     */
    protected array $judgeExpectations = [];

    protected ?string $name = null;

    protected string $input = '';

    protected object|string|null $judge = null;

    protected ?string $location = null;

    protected ?string $datasetPath = null;

    protected string $inputColumn = 'input';

    protected ?string $nameColumn = 'name';

    /**
     * @var array<int, string>
     */
    protected array $containsFromColumns = [];

    /**
     * @var array<int, string>
     */
    protected array $notContainsFromColumns = [];

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

    public function conversation(): ConversationEvalBuilder
    {
        return new ConversationEvalBuilder($this->agent, $this->runner, $this->resolveName(), $this->location ?? $this->resolveLocation(), $this->judge);
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

    public function expectRegex(string $pattern): self
    {
        $this->deterministicExpectations[] = [
            'type' => 'regex',
            'pattern' => $pattern,
        ];

        return $this;
    }

    /**
     * @param  string|array<int, string>  $values
     */
    public function expectNotContains(string|array $values): self
    {
        $this->deterministicExpectations[] = [
            'type' => 'not_contains',
            'values' => is_array($values) ? $values : [$values],
        ];

        return $this;
    }

    public function expectJson(): self
    {
        $this->deterministicExpectations[] = [
            'type' => 'json',
        ];

        return $this;
    }

    public function expectJsonPath(string $path, mixed $expected = null): self
    {
        $this->deterministicExpectations[] = [
            'type' => 'json_path',
            'path' => $path,
            'expected' => $expected,
            'has_expected' => func_num_args() >= 2,
        ];

        return $this;
    }

    public function expectLength(?int $min = null, ?int $max = null): self
    {
        if ($min === null && $max === null) {
            throw new InvalidArgumentException('expectLength requires a minimum or maximum length.');
        }

        if (($min !== null && $min < 0) || ($max !== null && $max < 0)) {
            throw new InvalidArgumentException('expectLength minimum and maximum must be zero or greater.');
        }

        if ($min !== null && $max !== null && $min > $max) {
            throw new InvalidArgumentException('expectLength minimum cannot be greater than maximum.');
        }

        $this->deterministicExpectations[] = [
            'type' => 'length',
            'min' => $min,
            'max' => $max,
        ];

        return $this;
    }

    public function expectStartsWith(string $value): self
    {
        $this->deterministicExpectations[] = [
            'type' => 'starts_with',
            'value' => $value,
        ];

        return $this;
    }

    public function expectEndsWith(string $value): self
    {
        $this->deterministicExpectations[] = [
            'type' => 'ends_with',
            'value' => $value,
        ];

        return $this;
    }

    public function expectToolCalled(string $name): self
    {
        $this->deterministicExpectations[] = [
            'type' => 'tool_called',
            'name' => $name,
        ];

        return $this;
    }

    public function expectToolNotCalled(string $name): self
    {
        $this->deterministicExpectations[] = [
            'type' => 'tool_not_called',
            'name' => $name,
        ];

        return $this;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function expectToolCalledWith(string $name, array $arguments): self
    {
        $this->deterministicExpectations[] = [
            'type' => 'tool_called_with',
            'name' => $name,
            'arguments' => $arguments,
        ];

        return $this;
    }

    public function expect(callable|object|string $expectation): self
    {
        $this->customExpectations[] = $expectation;

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

    public function dataset(string $path): self
    {
        $this->datasetPath = $path;

        return $this;
    }

    public function inputColumn(string $column): self
    {
        $this->inputColumn = $column;

        return $this;
    }

    public function nameColumn(?string $column): self
    {
        $this->nameColumn = $column;

        return $this;
    }

    public function expectContainsFrom(string $column): self
    {
        $this->containsFromColumns[] = $column;

        return $this;
    }

    public function expectNotContainsFrom(string $column): self
    {
        $this->notContainsFromColumns[] = $column;

        return $this;
    }

    public function run(): EvalResult|DatasetEvalResult
    {
        if ($this->datasetPath !== null) {
            return $this->runDataset();
        }

        return $this->runner->run(
            agent: $this->agent,
            name: $this->resolveName(),
            input: $this->input,
            contains: $this->contains,
            exact: $this->exact,
            judgeExpectations: $this->judgeExpectations,
            location: $this->location ?? $this->resolveLocation(),
            deterministicExpectations: $this->deterministicExpectations,
            customExpectations: $this->customExpectations,
        );
    }

    protected function runDataset(): DatasetEvalResult
    {
        $baseName = $this->resolveName();
        $datasetPath = $this->resolveDatasetPath($this->datasetPath ?? '');
        $rows = $this->loadDataset($datasetPath);
        $results = [];

        foreach ($rows as $index => $row) {
            $rowName = $this->resolveDatasetRowName($row, $index);
            $name = sprintf('%s / %s', $baseName, $rowName);
            $location = sprintf('%s:%d', $datasetPath, $index + 1);
            $input = $this->stringColumn($row, $this->inputColumn, $index);
            $contains = $this->contains;
            $deterministicExpectations = $this->deterministicExpectations;

            foreach ($this->containsFromColumns as $column) {
                array_push($contains, ...$this->stringListColumn($row, $column, $index));
            }

            foreach ($this->notContainsFromColumns as $column) {
                $deterministicExpectations[] = [
                    'type' => 'not_contains',
                    'values' => $this->stringListColumn($row, $column, $index),
                ];
            }

            $results[] = $this->runner->run(
                agent: $this->agent,
                name: $name,
                input: $input,
                contains: $contains,
                exact: $this->exact,
                judgeExpectations: $this->judgeExpectations,
                location: $location,
                deterministicExpectations: $deterministicExpectations,
                customExpectations: $this->customExpectations,
            );
        }

        return new DatasetEvalResult($baseName, $datasetPath, $results);
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

        $standaloneName = StandaloneEvalContext::currentName();

        if ($standaloneName !== null) {
            return $standaloneName;
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

        return 'unnamed-eval';
    }

    protected function resolveDatasetPath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return function_exists('base_path') ? base_path($path) : getcwd().DIRECTORY_SEPARATOR.$path;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function loadDataset(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException(sprintf('Dataset file [%s] does not exist.', $path));
        }

        if (str_ends_with($path, '.json')) {
            return $this->normalizeDatasetRows($this->loadJsonDataset($path), $path);
        }

        if (str_ends_with($path, '.php')) {
            return $this->normalizeDatasetRows($this->loadPhpDataset($path), $path);
        }

        if (str_ends_with($path, '.csv')) {
            return $this->normalizeDatasetRows($this->loadCsvDataset($path), $path);
        }

        throw new RuntimeException(sprintf('Dataset [%s] must be a JSON, PHP, or CSV file.', $path));
    }

    protected function loadJsonDataset(string $path): mixed
    {
        $decoded = json_decode((string) file_get_contents($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf('Dataset file [%s] contains invalid JSON: %s', $path, json_last_error_msg()));
        }

        return $decoded;
    }

    protected function loadPhpDataset(string $path): mixed
    {
        return require $path;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    protected function loadCsvDataset(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException(sprintf('Dataset file [%s] could not be opened.', $path));
        }

        try {
            $headers = fgetcsv($handle);

            if (! is_array($headers) || $headers === []) {
                throw new RuntimeException(sprintf('Dataset file [%s] must contain a header row.', $path));
            }

            $headers = array_map(static fn (?string $header): string => trim((string) $header), $headers);

            if (in_array('', $headers, true)) {
                throw new RuntimeException(sprintf('Dataset file [%s] contains an empty header column.', $path));
            }

            $rows = [];

            while (($values = fgetcsv($handle)) !== false) {
                if ($values === [null]) {
                    continue;
                }

                $row = [];

                foreach ($headers as $index => $header) {
                    $row[$header] = $values[$index] ?? null;
                }

                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeDatasetRows(mixed $dataset, string $path): array
    {
        if (! is_array($dataset)) {
            throw new RuntimeException(sprintf('Dataset file [%s] must return an array of rows.', $path));
        }

        if (array_is_list($dataset)) {
            $rows = $dataset;
        } elseif (isset($dataset['rows']) && is_array($dataset['rows']) && array_is_list($dataset['rows'])) {
            $rows = $dataset['rows'];
        } else {
            throw new RuntimeException(sprintf('Dataset file [%s] must return an array of rows.', $path));
        }

        foreach ($rows as $index => $row) {
            if (! is_array($row) || array_is_list($row)) {
                throw new RuntimeException(sprintf('Dataset row %d in [%s] must be an object.', $index + 1, $path));
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function resolveDatasetRowName(array $row, int $index): string
    {
        if ($this->nameColumn !== null) {
            $value = $this->columnValue($row, $this->nameColumn);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return sprintf('row %d', $index + 1);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function stringColumn(array $row, string $column, int $index): string
    {
        $value = $this->columnValue($row, $column);

        if (! is_scalar($value) && $value !== null) {
            throw new RuntimeException(sprintf('Dataset row %d column "%s" must be a stringable value.', $index + 1, $column));
        }

        if ($value === null) {
            throw new RuntimeException(sprintf('Dataset row %d is missing required column "%s".', $index + 1, $column));
        }

        return (string) $value;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, string>
     */
    protected function stringListColumn(array $row, string $column, int $index): array
    {
        $value = $this->columnValue($row, $column);

        if ($value === null) {
            throw new RuntimeException(sprintf('Dataset row %d is missing required column "%s".', $index + 1, $column));
        }

        $values = is_array($value) ? $value : [$value];

        foreach ($values as $item) {
            if (! is_scalar($item) && $item !== null) {
                throw new RuntimeException(sprintf('Dataset row %d column "%s" must contain stringable values.', $index + 1, $column));
            }
        }

        return array_map(static fn (mixed $item): string => (string) $item, $values);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function columnValue(array $row, string $column): mixed
    {
        $value = $row;

        foreach (explode('.', $column) as $segment) {
            if (! is_array($value)) {
                return null;
            }

            $key = ctype_digit($segment) ? (int) $segment : $segment;

            if (! array_key_exists($key, $value)) {
                return null;
            }

            $value = $value[$key];
        }

        return $value;
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

            if (! str_contains($file, '/tests/') && ! str_contains($file, '/workbench/evals/') && ! str_ends_with($file, '.eval.php')) {
                continue;
            }

            $line = isset($frame['line']) && is_int($frame['line']) ? $frame['line'] : 1;

            return sprintf('%s:%d', $frame['file'], $line);
        }

        return null;
    }
}
