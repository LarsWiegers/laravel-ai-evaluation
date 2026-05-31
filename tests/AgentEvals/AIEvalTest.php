<?php

declare(strict_types=1);

use LaravelAIEvaluation\AIEval;

it('passes contains expectation using agent class string', function () {
    $result = AIEval::agent(FakeSupportAgent::class)
        ->name('refund-policy')
        ->input('What is your refund policy?')
        ->expectContains(['refund', '30 days'])
        ->run();

    expect($result->passed())->toBeTrue();
});

it('passes exact expectation using agent instance', function () {
    $result = AIEval::agent(new FakeHealthcheckAgent)
        ->name('healthcheck')
        ->input('Reply with exactly: OK')
        ->expectExact('OK')
        ->run();

    expect($result->passed())->toBeTrue();
});

it('throws when expectations fail', function () {
    AIEval::agent(new FakeHealthcheckAgent)
        ->name('failing-case')
        ->input('Reply with exactly: NOT_OK')
        ->expectExact('NOT_OK')
        ->run()
        ->assertPasses();
})->throws(PHPUnit\Framework\ExpectationFailedException::class);

it('uses pest test name when name is omitted', function () {
    $result = AIEval::agent(new FakeHealthcheckAgent)
        ->input('Reply with exactly: WRONG')
        ->expectExact('WRONG')
        ->run();

    expect(fn () => $result->assertPasses())
        ->toThrow(PHPUnit\Framework\ExpectationFailedException::class, "AI eval 'it uses pest test name when name is omitted' failed");
});

it('runs json dataset rows through one eval builder', function () {
    $path = createDatasetFixture([
        [
            'name' => 'refund inside window',
            'input' => 'Can I get a refund?',
            'required_terms' => ['Refunds', '30 days'],
            'forbidden_terms' => ['guaranteed'],
        ],
        [
            'name' => 'billing question',
            'input' => 'Can I see invoice help?',
            'required_terms' => ['invoice'],
            'forbidden_terms' => ['refund'],
        ],
    ]);

    $result = AIEval::agent(new DatasetSupportAgent)
        ->name('support-dataset')
        ->dataset($path)
        ->inputColumn('input')
        ->expectContainsFrom('required_terms')
        ->expectNotContainsFrom('forbidden_terms')
        ->run();

    expect($result->passed())->toBeTrue();
    expect($result->results())->toHaveCount(2);
    expect($result->results()[0]->toArray()['name'])->toBe('support-dataset / refund inside window');
    expect($result->toArray()['total'])->toBe(2);
});

it('runs php dataset rows through one eval builder', function () {
    $path = createPhpDatasetFixture(<<<'PHP'
<?php

return [
    [
        'name' => 'refund inside window',
        'input' => 'Can I get a refund?',
        'required_terms' => ['Refunds', '30 days'],
    ],
    [
        'name' => 'billing question',
        'input' => 'Can I see invoice help?',
        'required_terms' => ['invoice'],
    ],
];
PHP);

    $result = AIEval::agent(new DatasetSupportAgent)
        ->name('support-php-dataset')
        ->dataset($path)
        ->expectContainsFrom('required_terms')
        ->run();

    expect($result->passed())->toBeTrue();
    expect($result->results())->toHaveCount(2);
    expect($result->results()[1]->toArray()['name'])->toBe('support-php-dataset / billing question');
});

it('runs php datasets with rows wrapper', function () {
    $path = createPhpDatasetFixture(<<<'PHP'
<?php

return [
    'rows' => [
        [
            'name' => 'wrapped row',
            'input' => 'Can I get a refund?',
            'required_terms' => ['Refunds'],
        ],
    ],
];
PHP);

    $result = AIEval::agent(new DatasetSupportAgent)
        ->name('wrapped-php-dataset')
        ->dataset($path)
        ->expectContainsFrom('required_terms')
        ->run();

    expect($result->passed())->toBeTrue();
    expect($result->results()[0]->toArray()['name'])->toBe('wrapped-php-dataset / wrapped row');
});

it('runs csv dataset rows through one eval builder', function () {
    $path = createCsvDatasetFixture(<<<'CSV'
name,input,required_term,forbidden_term
refund inside window,Can I get a refund?,Refunds,guaranteed
billing question,Can I see invoice help?,invoice,refund
CSV);

    $result = AIEval::agent(new DatasetSupportAgent)
        ->name('support-csv-dataset')
        ->dataset($path)
        ->expectContainsFrom('required_term')
        ->expectNotContainsFrom('forbidden_term')
        ->run();

    expect($result->passed())->toBeTrue();
    expect($result->results())->toHaveCount(2);
    expect($result->results()[0]->toArray()['name'])->toBe('support-csv-dataset / refund inside window');
});

it('throws when csv dataset is missing headers', function () {
    $path = createCsvDatasetFixture('');

    expect(function () use ($path): void {
        AIEval::agent(new DatasetSupportAgent)
            ->name('invalid-csv-dataset')
            ->dataset($path)
            ->expectContains('anything')
            ->run();
    })->toThrow(RuntimeException::class, 'must contain a header row');
});

it('fails dataset assertions with row level failures', function () {
    $path = createDatasetFixture([
        [
            'name' => 'missing expected terms',
            'input' => 'Can I get a refund?',
            'required_terms' => ['wire transfer'],
        ],
    ]);

    $result = AIEval::agent(new DatasetSupportAgent)
        ->name('support-dataset')
        ->dataset($path)
        ->expectContainsFrom('required_terms')
        ->run();

    expect($result->passed())->toBeFalse();
    expect($result->failures()[0])->toContain('Missing required substring(s): wire transfer');
    expect(fn () => $result->assertPasses())
        ->toThrow(PHPUnit\Framework\ExpectationFailedException::class, "AI eval dataset 'support-dataset' failed");
});

it('throws for invalid dataset files', function () {
    $path = createDatasetFixture(['input' => 'not a list']);

    expect(function () use ($path): void {
        AIEval::agent(new DatasetSupportAgent)
            ->name('invalid-dataset')
            ->dataset($path)
            ->expectContains('anything')
            ->run();
    })->toThrow(RuntimeException::class, 'must return an array of rows');
});

it('throws when php dataset does not return rows', function () {
    $path = createPhpDatasetFixture(<<<'PHP'
<?php

return 'not rows';
PHP);

    expect(function () use ($path): void {
        AIEval::agent(new DatasetSupportAgent)
            ->name('invalid-php-dataset')
            ->dataset($path)
            ->expectContains('anything')
            ->run();
    })->toThrow(RuntimeException::class, 'must return an array of rows');
});

it('runs flattened multi turn conversation evals', function () {
    $agent = new ConversationAgent;

    $result = AIEval::agent($agent)
        ->name('refund-conversation')
        ->conversation()
        ->user('I bought this last week.')
        ->assistantShouldContain('order number')
        ->user('The order is #123.')
        ->expectContains('refund')
        ->run();

    expect($result->passed())->toBeTrue();
    expect($agent->prompt)->toContain('User: I bought this last week.');
    expect($agent->prompt)->toContain('User: The order is #123.');
    expect($agent->prompt)->toEndWith('Assistant:');
});

it('supports forbidden text checks for conversation final responses', function () {
    $result = AIEval::agent(new ConversationAgent)
        ->name('safe-conversation')
        ->conversation()
        ->user('Can you guarantee my refund?')
        ->assistantShouldNotContain('guaranteed')
        ->run();

    expect($result->passed())->toBeTrue();
});

it('requires at least one user turn for conversation evals', function () {
    expect(function (): void {
        AIEval::agent(new ConversationAgent)
            ->name('empty-conversation')
            ->conversation()
            ->expectContains('anything')
            ->run();
    })->toThrow(InvalidArgumentException::class, 'Conversation evals require at least one user turn.');
});

it('runs dataset backed conversation eval rows', function () {
    $path = createDatasetFixture([
        [
            'name' => 'refund follow up',
            'turns' => [
                ['role' => 'user', 'content' => 'I bought this last week.'],
                ['role' => 'assistant', 'content' => 'Can you share your order number?'],
                ['role' => 'user', 'content' => 'The order is #123.'],
            ],
            'required_terms' => ['refund', 'order number'],
        ],
        [
            'name' => 'single input fallback',
            'input' => 'Can you help with a refund?',
            'required_terms' => ['refund'],
        ],
    ]);

    $result = AIEval::agent(new ConversationAgent)
        ->name('conversation-dataset')
        ->conversation()
        ->dataset($path)
        ->expectContainsFrom('required_terms')
        ->run();

    expect($result->passed())->toBeTrue();
    expect($result->results())->toHaveCount(2);
    expect($result->results()[0]->toArray()['name'])->toBe('conversation-dataset / refund follow up');
    expect($result->results()[0]->toArray()['input'])->toContain('Assistant: Can you share your order number?');
    expect($result->results()[1]->toArray()['input'])->toContain('User: Can you help with a refund?');
});

it('runs csv backed conversation rows as single user turns', function () {
    $path = createCsvDatasetFixture(<<<'CSV'
name,input,required_term
refund csv,Can you help with a refund?,refund
CSV);

    $result = AIEval::agent(new ConversationAgent)
        ->name('conversation-csv-dataset')
        ->conversation()
        ->dataset($path)
        ->expectContainsFrom('required_term')
        ->run();

    expect($result->passed())->toBeTrue();
    expect($result->results()[0]->toArray()['name'])->toBe('conversation-csv-dataset / refund csv');
    expect($result->results()[0]->toArray()['input'])->toContain('User: Can you help with a refund?');
});

it('validates conversation dataset turns', function () {
    $path = createDatasetFixture([
        [
            'name' => 'bad turn',
            'turns' => [
                ['role' => 'system', 'content' => 'Invalid role'],
            ],
            'required_terms' => ['refund'],
        ],
    ]);

    expect(function () use ($path): void {
        AIEval::agent(new ConversationAgent)
            ->name('invalid-conversation-dataset')
            ->conversation()
            ->dataset($path)
            ->expectContainsFrom('required_terms')
            ->run();
    })->toThrow(RuntimeException::class, 'role must be user or assistant');
});

class FakeSupportAgent
{
    public function prompt(string $prompt): string
    {
        return 'Our refund policy allows refunds within 30 days.';
    }
}

class FakeHealthcheckAgent
{
    public function prompt(string $prompt): string
    {
        return 'OK';
    }
}

class DatasetSupportAgent
{
    public function prompt(string $prompt): string
    {
        if (str_contains($prompt, 'invoice')) {
            return 'You can find invoice help in billing settings.';
        }

        return 'Refunds are available within 30 days.';
    }
}

class ConversationAgent
{
    public string $prompt = '';

    public function prompt(string $prompt): string
    {
        $this->prompt = $prompt;

        return 'Please share your order number so I can check refund eligibility.';
    }
}

function createDatasetFixture(array $rows): string
{
    static $registered = false;
    static $files = [];

    if (! $registered) {
        register_shutdown_function(static function () use (&$files): void {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        });

        $registered = true;
    }

    $relativePath = 'tests/tmp-evals/dataset-'.uniqid('', true).'.json';
    $absolutePath = base_path($relativePath);

    if (! is_dir(dirname($absolutePath))) {
        mkdir(dirname($absolutePath), 0777, true);
    }

    file_put_contents($absolutePath, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $files[] = $absolutePath;

    return $relativePath;
}

function createPhpDatasetFixture(string $contents): string
{
    static $registered = false;
    static $files = [];

    if (! $registered) {
        register_shutdown_function(static function () use (&$files): void {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        });

        $registered = true;
    }

    $relativePath = 'tests/tmp-evals/dataset-'.uniqid('', true).'.php';
    $absolutePath = base_path($relativePath);

    if (! is_dir(dirname($absolutePath))) {
        mkdir(dirname($absolutePath), 0777, true);
    }

    file_put_contents($absolutePath, $contents);

    $files[] = $absolutePath;

    return $relativePath;
}

function createCsvDatasetFixture(string $contents): string
{
    static $registered = false;
    static $files = [];

    if (! $registered) {
        register_shutdown_function(static function () use (&$files): void {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        });

        $registered = true;
    }

    $relativePath = 'tests/tmp-evals/dataset-'.uniqid('', true).'.csv';
    $absolutePath = base_path($relativePath);

    if (! is_dir(dirname($absolutePath))) {
        mkdir(dirname($absolutePath), 0777, true);
    }

    file_put_contents($absolutePath, $contents);

    $files[] = $absolutePath;

    return $relativePath;
}
