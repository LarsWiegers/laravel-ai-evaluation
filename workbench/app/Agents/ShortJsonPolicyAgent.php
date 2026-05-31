<?php

declare(strict_types=1);

namespace Workbench\App\Agents;

class ShortJsonPolicyAgent
{
    public function prompt(string $prompt): string
    {
        return '{"status":"eligible","days":30,"next_step":"contact support"}';
    }
}
