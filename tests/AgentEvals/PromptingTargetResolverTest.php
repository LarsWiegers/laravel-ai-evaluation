<?php

declare(strict_types=1);

use Illuminate\Broadcasting\Channel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\QueuedAgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use LaravelAIEvaluation\Evaluation\Support\PromptingTargetResolver;

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
    $agent = new class implements Agent {
        public function instructions(): Stringable|string
        {
            return 'instructions';
        }

        public function prompt(string $prompt, array $attachments = [], Lab|array|string|null $provider = null, ?string $model = null): AgentResponse
        {
            throw new RuntimeException('not called');
        }

        public function stream(string $prompt, array $attachments = [], Lab|array|string|null $provider = null, ?string $model = null): StreamableAgentResponse
        {
            throw new RuntimeException('not called');
        }

        public function queue(string $prompt, array $attachments = [], Lab|array|string|null $provider = null, ?string $model = null): QueuedAgentResponse
        {
            throw new RuntimeException('not called');
        }

        public function broadcast(string $prompt, Channel|array $channels, array $attachments = [], bool $now = false, Lab|array|string|null $provider = null, ?string $model = null): StreamableAgentResponse
        {
            throw new RuntimeException('not called');
        }

        public function broadcastNow(string $prompt, Channel|array $channels, array $attachments = [], Lab|array|string|null $provider = null, ?string $model = null): StreamableAgentResponse
        {
            throw new RuntimeException('not called');
        }

        public function broadcastOnQueue(string $prompt, Channel|array $channels, array $attachments = [], Lab|array|string|null $provider = null, ?string $model = null): QueuedAgentResponse
        {
            throw new RuntimeException('not called');
        }
    };

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
