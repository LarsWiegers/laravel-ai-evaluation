<?php

declare(strict_types=1);

namespace Tests\Fixtures\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class SalesQualificationAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You qualify inbound leads by identifying company size, timeline, and budget before routing to sales.';
    }
}
