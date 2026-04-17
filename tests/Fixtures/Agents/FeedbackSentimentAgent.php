<?php

declare(strict_types=1);

namespace Tests\Fixtures\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class FeedbackSentimentAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You classify customer feedback into sentiment, theme, urgency, and recommended next action.';
    }
}
