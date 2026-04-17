<?php

declare(strict_types=1);

namespace Tests\Fixtures\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class CustomerSupportAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are a customer support agent. Confirm policy details, set expectations, and provide the next action.';
    }
}
