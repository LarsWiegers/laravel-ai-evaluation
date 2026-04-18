<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use LaravelAIEvaluation\Standalone\StandaloneEvalSuite;
use Tests\Fixtures\Agents\AppointmentBookingAgent;

return function (StandaloneEvalSuite $suite): void {
    $suite->eval('appointment-booking-agent', function () {
        return AIEval::agent(AppointmentBookingAgent::class)
            ->input('Reply in one short sentence and include these exact tokens: Appointment confirmed, UTC, reminder enabled.')
            ->expectContains(['Appointment confirmed', 'UTC', 'reminder enabled'])
            ->run();
    });
};
