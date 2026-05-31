<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation;

class ToolCallRecorder
{
    /**
     * @var array<int, ToolCall>|null
     */
    protected static ?array $current = null;

    public static function start(): void
    {
        self::$current = [];
    }

    /**
     * @return array<int, ToolCall>
     */
    public static function stop(): array
    {
        $toolCalls = self::$current ?? [];

        self::$current = null;

        return $toolCalls;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public static function record(string $name, array $arguments = [], ?string $id = null, ?string $source = 'manual'): void
    {
        if (self::$current === null) {
            return;
        }

        self::$current[] = new ToolCall($name, $arguments, $id, $source);
    }
}
