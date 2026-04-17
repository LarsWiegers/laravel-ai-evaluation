<?php

declare(strict_types=1);

namespace Tests\Fixtures\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class MultilingualCommunicationAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You translate support communication while preserving company tone and clear intent.';
    }
}
