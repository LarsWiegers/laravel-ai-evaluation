<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Console\Reports;

class TextStandaloneReportFormatter implements StandaloneReportFormatter
{
    public function format(StandaloneEvalRunReport $report): string
    {
        $output = '';

        foreach ($report->cases as $case) {
            if ($case->passed) {
                $output .= sprintf("<fg=green;options=bold>PASS %s</>\n", $case->name);

                continue;
            }

            $output .= sprintf("<fg=red;options=bold>%s %s</>\n", $case->errored() ? 'ERROR' : 'FAIL', $case->name);

            foreach ($case->failures as $failure) {
                $output .= sprintf("  - %s\n", $failure);
            }

            if ($this->hasContainsExpectationFailure($case) && $case->output !== null) {
                $output .= "  Returned output:\n";
                $output .= sprintf("    %s\n", $this->formatOutputBlock($case->output));
            }
        }

        if (! $report->matchedFilter) {
            $output .= "No standalone eval names matched the provided filter.\n";
        }

        if ($report->total() > 0) {
            $output .= sprintf("\nStandalone eval summary: total=%d passed=%d failed=%d\n", $report->total(), $report->passed(), $report->failed());
        }

        return $output;
    }

    protected function hasContainsExpectationFailure(StandaloneEvalCaseResult $case): bool
    {
        foreach ($case->expectationResults as $expectationResult) {
            if (($expectationResult['type'] ?? null) !== 'contains') {
                continue;
            }

            if (($expectationResult['passed'] ?? false) === false) {
                return true;
            }
        }

        return false;
    }

    protected function formatOutputBlock(string $output): string
    {
        $normalized = trim($output);

        if ($normalized === '') {
            return '(empty)';
        }

        return str_replace("\n", "\n    ", $normalized);
    }
}
