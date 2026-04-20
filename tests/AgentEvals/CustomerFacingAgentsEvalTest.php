<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;
use Tests\Fixtures\Agents\AppointmentBookingAgent;
use Tests\Fixtures\Agents\BillingInvoiceAgent;
use Tests\Fixtures\Agents\CustomerSupportAgent;
use Tests\Fixtures\Agents\EcommerceConciergeAgent;
use Tests\Fixtures\Agents\FeedbackSentimentAgent;
use Tests\Fixtures\Agents\InternalOpsAssistantAgent;
use Tests\Fixtures\Agents\KnowledgeBaseAgent;
use Tests\Fixtures\Agents\MultilingualCommunicationAgent;
use Tests\Fixtures\Agents\OnboardingCoachAgent;
use Tests\Fixtures\Agents\SalesQualificationAgent;

dataset('customer-facing-agents', [
    'customer-support-agent' => [
        CustomerSupportAgent::class,
        'Reply in one short sentence and include these exact tokens: refunds, 30 days, support.',
        ['refunds', '30 days', 'support'],
    ],
    'sales-qualification-agent' => [
        SalesQualificationAgent::class,
        'Reply in one short sentence and include these exact tokens: company_size, timeline, score=high.',
        ['company_size', 'timeline', 'score=high'],
    ],
    'appointment-booking-agent' => [
        AppointmentBookingAgent::class,
        'Reply in one short sentence and include these exact tokens: Appointment confirmed, UTC, reminder enabled.',
        ['Appointment confirmed', 'UTC', 'reminder enabled'],
    ],
    'ecommerce-concierge-agent' => [
        EcommerceConciergeAgent::class,
        'Reply in one short sentence and include these exact tokens: recommendation, shipping, returns.',
        ['recommendation', 'shipping', 'returns'],
    ],
    'onboarding-coach-agent' => [
        OnboardingCoachAgent::class,
        'Reply in one short sentence and include these exact tokens: Onboarding checklist, invite your team, first workflow.',
        ['Onboarding checklist', 'invite your team', 'first workflow'],
    ],
    'billing-invoice-agent' => [
        BillingInvoiceAgent::class,
        'Reply in one short sentence and include these exact tokens: Invoice status, payment failed, update payment method.',
        ['Invoice status', 'payment failed', 'update payment method'],
    ],
    'knowledge-base-agent' => [
        KnowledgeBaseAgent::class,
        'Reply in one short sentence and include these exact tokens: knowledge base, SSO setup, Source:.',
        ['knowledge base', 'SSO setup', 'Source:'],
    ],
    'internal-ops-assistant-agent' => [
        InternalOpsAssistantAgent::class,
        'Reply in one short sentence and include these exact tokens: Ops summary, priority=high, escalation sent.',
        ['Ops summary', 'priority=high', 'escalation sent'],
    ],
    'feedback-sentiment-agent' => [
        FeedbackSentimentAgent::class,
        'Reply in one short sentence and include these exact tokens: sentiment=negative, theme=delivery delay, follow_up.',
        ['sentiment=negative', 'theme=delivery delay', 'follow_up'],
    ],
    'multilingual-communication-agent' => [
        MultilingualCommunicationAgent::class,
        'Reply in one short sentence and include these exact tokens: Translation (es), Tone: friendly, next steps.',
        ['Translation (es)', 'Tone: friendly', 'next steps'],
    ],
]);

it('evaluates customer-facing agents built for Laravel teams', function (string $agentClass, string $prompt, array $expectations) {
    if (! liveAiCredentialsConfigured()) {
        $this->markTestSkipped('Live AI evals require at least one configured provider API key.');
    }

    $result = AIEval::agent($agentClass)
        ->input($prompt)
        ->expectContains($expectations)
        ->run();

    expect($result->passed())->toBeTrue();
})->with('customer-facing-agents')->group('live-ai');

function liveAiCredentialsConfigured(): bool
{
    foreach ([
        'ANTHROPIC_API_KEY',
        'GEMINI_API_KEY',
        'MISTRAL_API_KEY',
        'OPENAI_API_KEY',
        'XAI_API_KEY',
        'OLLAMA_API_KEY',
    ] as $key) {
        if (is_string(env($key)) && trim((string) env($key)) !== '') {
            return true;
        }
    }

    return false;
}
