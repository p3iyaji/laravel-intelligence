<?php

namespace ProjectAnalyzer\Commands;

use Illuminate\Console\Command;
use ProjectAnalyzer\Engine\AnalysisEngine;
use ProjectAnalyzer\Generators\ReportExporter;

class ExportReportCommand extends Command
{
    protected $signature = 'project:analyze:export {--format=json : Export format (json, markdown, html)} {--output= : Output file path}';

    protected $description = 'Export analysis results to a file';

    public function handle(AnalysisEngine $engine, ReportExporter $exporter): int
    {
        $format = $this->option('format');
        $result = $engine->analyze();
        $path = $exporter->export($result, $format, $this->option('output'));

        $this->info("Report exported to: {$path}");

        return self::SUCCESS;
    }
}
