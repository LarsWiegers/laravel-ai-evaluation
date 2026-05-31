<?php

declare(strict_types=1);

namespace Workbench\App\Agents;

class UnsafeRefundPolicyAgent
{
    public function prompt(string $prompt): string
    {
        return 'Refunds are always approved with no review.';
    }
}
