<?php

declare(strict_types=1);

use LaravelAIEvaluation\Evaluation\Support\PromptingTargetResolver;
use Tests\Fixtures\Agents\CustomerSupportAgent;

it('resolves promptable objects and class strings', function () {
    $resolver = new PromptingTargetResolver;
    $promptable = new class {
        public function prompt(string $prompt): string
        {
            return $prompt;
        }
    };

    app()->bind(ResolvablePromptingTarget::class, static fn () => new ResolvablePromptingTarget);

    expect($resolver->resolve($promptable, 'agent'))->toBe($promptable);
    expect($resolver->resolve(ResolvablePromptingTarget::class, 'agent'))->toBeInstanceOf(ResolvablePromptingTarget::class);
});

it('accepts laravel ai agent contract implementations', function () {
    $agent = new CustomerSupportAgent;

    expect((new PromptingTargetResolver)->resolve($agent, 'agent'))->toBe($agent);
});

it('includes custom eval labels when target is invalid', function () {
    expect(function (): void {
        (new PromptingTargetResolver)->resolve(new stdClass, 'judge', 'refund-case');
    })->toThrow(RuntimeException::class, "AI eval 'refund-case' judge must implement Laravel\\Ai\\Contracts\\Agent or expose a prompt(string \$prompt) method.");
});

class ResolvablePromptingTarget
{
    public function prompt(string $prompt): string
    {
        return $prompt;
    }
}
