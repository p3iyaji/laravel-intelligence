<?php

namespace ProjectAnalyzer\Commands;

use Illuminate\Console\Command;
use ProjectAnalyzer\Engine\AnalysisEngine;
use ProjectAnalyzer\Generators\ReportExporter;

class GenerateDocsCommand extends Command
{
    protected $signature = 'project:analyze:docs {--format=markdown : Documentation format}';

    protected $description = 'Generate project documentation';

    public function handle(AnalysisEngine $engine, ReportExporter $exporter): int
    {
        $this->info('Generating documentation...');
        $result = $engine->analyze();
        $format = $this->option('format');
        $path = $exporter->export($result, $format);

        $this->info("Documentation generated: {$path}");

        return self::SUCCESS;
    }
}
