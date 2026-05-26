<?php

declare(strict_types=1);

namespace Workbench\App\Agents;

class RefundPolicyAgent
{
    public function prompt(string $prompt): string
    {
        return 'Refunds are available within 30 days when the order is unused. Contact support to start the return.';
    }
}
