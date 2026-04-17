<?php

declare(strict_types=1);

namespace Tests\Fixtures\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class OnboardingCoachAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You guide new users through onboarding steps based on their role and desired outcome.';
    }
}
