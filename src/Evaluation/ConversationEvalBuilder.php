<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation;

use InvalidArgumentException;
use RuntimeException;

class ConversationEvalBuilder
{
    /**
     * @var array<int, array{role: string, content: string}>
     */
    protected array $turns = [];

    /**
     * @var array<int, string>
     */
    protected array $assistantContains = [];

    /**
     * @var array<int, string>
     */
    protected array $assistantNotContains = [];

    protected EvalCaseBuilder $finalBuilder;

    protected ?string $datasetPath = null;

    protected string $turnsColumn = 'turns';

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
        object|string $agent,
        ?EvalRunner $runner,
        protected string $name,
        protected ?string $location,
        object|string|null $judge = null,
    ) {
        $this->finalBuilder = new EvalCaseBuilder($agent, $runner);
        $this->finalBuilder->name($name);

        if ($location !== null) {
            $this->finalBuilder->location($location);
        }

        if ($judge !== null) {
            $this->finalBuilder->useJudge($judge);
        }
    }

    public function user(string $message): self
    {
        $this->turns[] = ['role' => 'user', 'content' => $message];

        return $this;
    }

    /**
     * @param  string|array<int, string>  $values
     */
    public function assistantShouldContain(string|array $values): self
    {
        foreach (is_array($values) ? $values : [$values] as $value) {
            $this->assistantContains[] = $value;
        }

        return $this;
    }

    /**
     * @param  string|array<int, string>  $values
     */
    public function assistantShouldNotContain(string|array $values): self
    {
        foreach (is_array($values) ? $values : [$values] as $value) {
            $this->assistantNotContains[] = $value;
        }

        return $this;
    }

    public function dataset(string $path): self
    {
        $this->datasetPath = $path;

        return $this;
    }

    public function turnsColumn(string $column): self
    {
        $this->turnsColumn = $column;

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

    /**
     * @param  string|array<int, string>  $contains
     */
    public function expectContains(string|array $contains): self
    {
        $this->finalBuilder->expectContains($contains);

        return $this;
    }

    public function expectExact(string $exact): self
    {
        $this->finalBuilder->expectExact($exact);

        return $this;
    }

    public function expectRegex(string $pattern): self
    {
        $this->finalBuilder->expectRegex($pattern);

        return $this;
    }

    /**
     * @param  string|array<int, string>  $values
     */
    public function expectNotContains(string|array $values): self
    {
        $this->finalBuilder->expectNotContains($values);

        return $this;
    }

    public function expectJson(): self
    {
        $this->finalBuilder->expectJson();

        return $this;
    }

    public function expectJsonPath(string $path, mixed $expected = null): self
    {
        func_num_args() >= 2
            ? $this->finalBuilder->expectJsonPath($path, $expected)
            : $this->finalBuilder->expectJsonPath($path);

        return $this;
    }

    public function expectLength(?int $min = null, ?int $max = null): self
    {
        $this->finalBuilder->expectLength($min, $max);

        return $this;
    }

    public function expectStartsWith(string $value): self
    {
        $this->finalBuilder->expectStartsWith($value);

        return $this;
    }

    public function expectEndsWith(string $value): self
    {
        $this->finalBuilder->expectEndsWith($value);

        return $this;
    }

    public function expect(callable|object|string $expectation): self
    {
        $this->finalBuilder->expect($expectation);

        return $this;
    }

    public function expectJudge(string $criteria, ?float $threshold = null, object|string|null $judge = null): self
    {
        $this->finalBuilder->expectJudge($criteria, $threshold, $judge);

        return $this;
    }

    public function expectJudgeAgainst(string $reference, string $criteria, ?float $threshold = null, object|string|null $judge = null): self
    {
        $this->finalBuilder->expectJudgeAgainst($reference, $criteria, $threshold, $judge);

        return $this;
    }

    public function run(): EvalResult|DatasetEvalResult
    {
        if ($this->datasetPath !== null) {
            return $this->runDataset();
        }

        if ($this->turns === []) {
            throw new InvalidArgumentException('Conversation evals require at least one user turn.');
        }

        return $this->runSingle($this->finalBuilder, $this->turns);
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $turns
     */
    protected function runSingle(EvalCaseBuilder $builder, array $turns): EvalResult|DatasetEvalResult
    {
        foreach ($this->assistantContains as $value) {
            $builder->expectContains($value);
        }

        foreach ($this->assistantNotContains as $value) {
            $builder->expectNotContains($value);
        }

        return $builder
            ->input($this->prompt($turns))
            ->run();
    }

    protected function runDataset(): DatasetEvalResult
    {
        $datasetPath = $this->resolveDatasetPath($this->datasetPath ?? '');
        $rows = $this->loadDataset($datasetPath);
        $results = [];

        foreach ($rows as $index => $row) {
            $turns = $this->rowTurns($row, $index);
            $rowName = $this->resolveDatasetRowName($row, $index);
            $builder = clone $this->finalBuilder;

            $builder->name(sprintf('%s / %s', $this->name, $rowName));
            $builder->location(sprintf('%s:%d', $datasetPath, $index + 1));

            foreach ($this->containsFromColumns as $column) {
                $builder->expectContains($this->stringListColumn($row, $column, $index));
            }

            foreach ($this->notContainsFromColumns as $column) {
                $builder->expectNotContains($this->stringListColumn($row, $column, $index));
            }

            $results[] = $this->runSingle($builder, $turns);
        }

        return new DatasetEvalResult($this->name, $datasetPath, $results);
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $turns
     */
    protected function prompt(array $turns): string
    {
        $lines = [
            'Continue the following conversation as the assistant.',
            'Respond only with the assistant response for the next turn.',
            '',
        ];

        foreach ($turns as $turn) {
            $lines[] = sprintf('%s: %s', ucfirst($turn['role']), $turn['content']);
        }

        $lines[] = 'Assistant:';

        return implode(PHP_EOL, $lines);
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
            return $this->normalizeDatasetRows(require $path, $path);
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
            $rows = [];

            while (($values = fgetcsv($handle)) !== false) {
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
     * @return array<int, array{role: string, content: string}>
     */
    protected function rowTurns(array $row, int $index): array
    {
        $turns = $this->columnValue($row, $this->turnsColumn);

        if ($turns === null) {
            return [['role' => 'user', 'content' => $this->stringColumn($row, $this->inputColumn, $index)]];
        }

        if (! is_array($turns) || ! array_is_list($turns) || $turns === []) {
            throw new RuntimeException(sprintf('Dataset row %d column "%s" must be a non-empty array of turns.', $index + 1, $this->turnsColumn));
        }

        $normalized = [];

        foreach ($turns as $turnIndex => $turn) {
            if (! is_array($turn) || ! isset($turn['role'], $turn['content']) || ! is_scalar($turn['role']) || ! is_scalar($turn['content'])) {
                throw new RuntimeException(sprintf('Dataset row %d turn %d must include stringable role and content values.', $index + 1, $turnIndex + 1));
            }

            $role = strtolower((string) $turn['role']);

            if (! in_array($role, ['user', 'assistant'], true)) {
                throw new RuntimeException(sprintf('Dataset row %d turn %d role must be user or assistant.', $index + 1, $turnIndex + 1));
            }

            $normalized[] = ['role' => $role, 'content' => (string) $turn['content']];
        }

        return $normalized;
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
}
