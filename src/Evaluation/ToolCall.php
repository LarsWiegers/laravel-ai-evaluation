<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation;

class ToolCall
{
    /**
     * @param  array<string, mixed>  $arguments
     */
    public function __construct(
        public readonly string $name,
        public readonly array $arguments = [],
        public readonly ?string $id = null,
        public readonly ?string $source = null,
    ) {
    }

    /**
     * @return array{id: string|null, name: string, arguments: array<string, mixed>, source: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'arguments' => $this->arguments,
            'source' => $this->source,
        ];
    }
}
