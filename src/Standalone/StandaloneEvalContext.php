<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Standalone;

class StandaloneEvalContext
{
    /**
     * @var array<int, string>
     */
    protected static array $nameStack = [];

    public static function currentName(): ?string
    {
        $name = end(self::$nameStack);

        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        return $name;
    }

    public static function withName(string $name, callable $callback): mixed
    {
        self::$nameStack[] = $name;

        try {
            return $callback();
        } finally {
            array_pop(self::$nameStack);
        }
    }
}
