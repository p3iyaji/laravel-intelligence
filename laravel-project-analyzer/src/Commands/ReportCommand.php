<?php

namespace ProjectAnalyzer\Commands;

use Illuminate\Console\Command;
use ProjectAnalyzer\Engine\AnalysisEngine;
use ProjectAnalyzer\Generators\ReportExporter;

class ReportCommand extends Command
{
    protected $signature = 'project:analyze:report {--format=html : Report format}';

    protected $description = 'Generate analysis report';

    public function handle(AnalysisEngine $engine, ReportExporter $exporter): int
    {
        $format = $this->option('format');
        $result = $engine->analyze();
        $path = $exporter->export($result, $format);

        $this->info("Report generated: {$path}");

        return self::SUCCESS;
    }
}
