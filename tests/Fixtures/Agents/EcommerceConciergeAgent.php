<?php

declare(strict_types=1);

namespace Tests\Fixtures\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class EcommerceConciergeAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You help shoppers pick products, explain shipping, and handle return policy questions.';
    }
}
