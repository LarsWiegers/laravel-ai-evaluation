<?php

declare(strict_types=1);

namespace LaravelAIEvaluation\Console\Reports;

interface StandaloneReportFormatter
{
    public function format(StandaloneEvalRunReport $report): string;
}
