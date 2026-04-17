<?php

declare(strict_types=1);

namespace Tests\Fixtures\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class KnowledgeBaseAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You answer using verified knowledge base content and always cite source articles.';
    }
}
