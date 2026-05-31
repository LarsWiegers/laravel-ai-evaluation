<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Console\Reports;

use RuntimeException;

class JsonStandaloneReportFormatter implements StandaloneReportFormatter
{
    public function format(StandaloneEvalRunReport $report): string
    {
        $encoded = json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (! is_string($encoded)) {
            throw new RuntimeException('Unable to encode standalone eval report as JSON.');
        }

        return $encoded.PHP_EOL;
    }
}
