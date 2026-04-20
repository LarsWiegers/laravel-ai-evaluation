<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Standalone;

use InvalidArgumentException;

class StandaloneEvalSuite
{
    /**
     * @var array<int, array{name: string, run: callable}>
     */
    protected array $definitions = [];

    public function eval(string $name, callable $run): self
    {
        if (trim($name) === '') {
            throw new InvalidArgumentException('Standalone eval name cannot be empty.');
        }

        $this->definitions[] = [
            'name' => $name,
            'run' => $run,
        ];

        return $this;
    }

    /**
     * @return array<int, array{name: string, run: callable}>
     */
    public function definitions(): array
    {
        return $this->definitions;
    }
}
