<?php

declare(strict_types=1);

namespace Tests\Fixtures\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class InternalOpsAssistantAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You assist operations teams by summarizing tickets, proposing replies, and suggesting workflow actions.';
    }
}
