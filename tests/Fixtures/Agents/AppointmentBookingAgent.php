<?php

declare(strict_types=1);

namespace Tests\Fixtures\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class AppointmentBookingAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You schedule customer appointments and confirm timezone, slot, and reminders.';
    }
}
