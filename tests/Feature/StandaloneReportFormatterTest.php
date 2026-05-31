<?php

declare(strict_types=1);

use LaravelAIEvaluation\Console\Reports\GithubStandaloneReportFormatter;
use LaravelAIEvaluation\Console\Reports\JsonStandaloneReportFormatter;
use LaravelAIEvaluation\Console\Reports\JunitStandaloneReportFormatter;
use LaravelAIEvaluation\Console\Reports\StandaloneEvalCaseResult;
use LaravelAIEvaluation\Console\Reports\StandaloneEvalRunReport;
use LaravelAIEvaluation\Console\Reports\TextStandaloneReportFormatter;

it('escapes github annotation properties and data', function () {
    $report = new StandaloneEvalRunReport([
        new StandaloneEvalCaseResult(
            name: 'refund: case, 100%',
            passed: false,
            location: base_path('tests/AgentEvals/RefundCase.eval.php').':42',
            failures: ["Missing refund\nScore: 50%"],
        ),
    ]);

    $output = (new GithubStandaloneReportFormatter)->format($report);

    expect($output)->toContain('file=tests/AgentEvals/RefundCase.eval.php,line=42,title=AI eval failed%3A refund%3A case%2C 100%25');
    expect($output)->toContain('::Missing refund%0AScore: 50%25');
});

it('formats junit failures and errors with escaped xml', function () {
    $report = new StandaloneEvalRunReport([
        new StandaloneEvalCaseResult(
            name: 'passing <case>',
            passed: true,
            location: base_path('tests/AgentEvals/Passing.eval.php'),
        ),
        new StandaloneEvalCaseResult(
            name: 'failing & case',
            passed: false,
            location: base_path('tests/AgentEvals/Failing.eval.php').':9',
            failures: ['Expected "refund" & got <none>'],
        ),
        new StandaloneEvalCaseResult(
            name: 'errored case',
            passed: false,
            failures: ['Boom <bad>'],
            exceptionClass: RuntimeException::class,
        ),
    ]);

    $output = (new JunitStandaloneReportFormatter)->format($report);

    expect($output)->toContain('<testsuites tests="3" failures="1" errors="1">');
    expect($output)->toContain('name="passing &lt;case&gt;"');
    expect($output)->toContain('file="tests/AgentEvals/Failing.eval.php" line="9"');
    expect($output)->toContain('<failure message="Expected &quot;refund&quot; &amp; got &lt;none&gt;">');
    expect($output)->toContain('<error message="Boom &lt;bad&gt;">');
});

it('formats json reports with unicode and slash values preserved', function () {
    $report = new StandaloneEvalRunReport([
        new StandaloneEvalCaseResult(name: 'unicode', passed: true, output: 'cafe refund / policy'),
    ]);

    $output = (new JsonStandaloneReportFormatter)->format($report);
    $payload = json_decode($output, true);

    expect($output)->toEndWith(PHP_EOL);
    expect($payload['type'])->toBe('ai_eval_standalone_report');
    expect($payload['cases'][0]['output'])->toBe('cafe refund / policy');
});

it('formats text reports for errors empty output and unmatched filters', function () {
    $report = new StandaloneEvalRunReport([
        new StandaloneEvalCaseResult(
            name: 'errored-case',
            passed: false,
            failures: ['Runtime failure'],
            exceptionClass: RuntimeException::class,
        ),
        new StandaloneEvalCaseResult(
            name: 'empty-output-case',
            passed: false,
            failures: ['Missing required substring: refund'],
            output: " \n ",
            expectationResults: [['type' => 'contains', 'passed' => false]],
        ),
    ], matchedFilter: false);

    $output = (new TextStandaloneReportFormatter)->format($report);

    expect($output)->toContain('<fg=red;options=bold>ERROR errored-case</>');
    expect($output)->toContain('Returned output:'.PHP_EOL.'    (empty)');
    expect($output)->toContain('No standalone eval names matched the provided filter.');
    expect($output)->toContain('Standalone eval summary: total=2 passed=0 failed=2');
});
