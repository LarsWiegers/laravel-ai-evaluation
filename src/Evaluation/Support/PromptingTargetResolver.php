<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Evaluation\Support;

use Laravel\Ai\Contracts\Agent;
use RuntimeException;

class PromptingTargetResolver
{
    public function resolve(object|string $target, string $type, ?string $name = null): object
    {
        $resolved = is_string($target) ? app()->make($target) : $target;

        if ($resolved instanceof Agent || is_callable([$resolved, 'prompt'])) {
            return $resolved;
        }

        $label = $name !== null && trim($name) !== ''
            ? sprintf("AI eval '%s'", $name)
            : 'AI eval';

        throw new RuntimeException(sprintf(
            '%s %s must implement Laravel\\Ai\\Contracts\\Agent or expose a prompt(string $prompt) method.',
            $label,
            $type,
        ));
    }
}
