<?php

namespace ProjectAnalyzer\Commands;

use Illuminate\Console\Command;
use ProjectAnalyzer\Engine\AnalysisEngine;

class TestSuggestionsCommand extends Command
{
    protected $signature = 'project:analyze:tests {--suggest : Show test suggestions}';

    protected $description = 'Analyze test coverage and suggest missing tests';

    public function handle(AnalysisEngine $engine): int
    {
        $result = $engine->analyze(['analyzers' => 'test']);
        $testData = $result->data['test'] ?? [];

        $this->info('Test Coverage: '.($testData['coverage_estimate'] ?? 0).'%');
        $this->info('Total Tests: '.($testData['total_tests'] ?? 0));

        if ($this->option('suggest')) {
            $missing = $testData['missing_tests'] ?? [];
            if (empty($missing)) {
                $this->info('No missing tests detected!');
            } else {
                $this->warn('Missing tests ('.count($missing).'):');
                foreach ($missing as $item) {
                    $this->line("  [{$item['type']}] {$item['class']} - {$item['suggestion']}");
                }
            }
        }

        return self::SUCCESS;
    }
}
