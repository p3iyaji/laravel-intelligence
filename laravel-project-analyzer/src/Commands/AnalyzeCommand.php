<?php

namespace ProjectAnalyzer\Commands;

use Illuminate\Console\Command;
use ProjectAnalyzer\Engine\AnalysisEngine;

class AnalyzeCommand extends Command
{
    protected $signature = 'project:analyze
        {--quick : Use cached results if available}
        {--path= : Specific path to analyze}
        {--analyzers= : Comma-separated list of analyzers}
        {--ci : CI/CD mode output}
        {--json= : Export JSON to file}';

    protected $description = 'Perform comprehensive static analysis of the Laravel project';

    public function handle(AnalysisEngine $engine): int
    {
        $this->info('Starting project analysis...');

        $options = [
            'quick' => $this->option('quick'),
            'analyzers' => $this->option('analyzers'),
        ];

        if ($path = $this->option('path')) {
            $options['paths'] = [$path];
        }

        $start = microtime(true);
        $result = $engine->analyze($options);
        $duration = round(microtime(true) - $start, 2);

        $metrics = $result->metrics;
        $stats = $metrics['statistics'] ?? [];

        if ($this->option('ci')) {
            $this->line(json_encode($result->toArray()));
        } else {
            $this->table(['Metric', 'Value'], [
                ['Health Score', $metrics['overall'] ?? 'N/A'],
                ['Total Classes', $stats['total_classes'] ?? 0],
                ['Total Files', $stats['total_files'] ?? 0],
                ['Total Models', $stats['total_models'] ?? 0],
                ['Total Controllers', $stats['total_controllers'] ?? 0],
                ['Test Coverage', ($metrics['coverage']['overall'] ?? 0).'%'],
                ['Recommendations', count($result->recommendations)],
                ['Duration', "{$duration}s"],
            ]);
        }

        if ($jsonPath = $this->option('json')) {
            file_put_contents($jsonPath, json_encode($result->toArray(), JSON_PRETTY_PRINT));
            $this->info("JSON exported to: {$jsonPath}");
        }

        $this->info('Analysis complete!');

        return self::SUCCESS;
    }
}
