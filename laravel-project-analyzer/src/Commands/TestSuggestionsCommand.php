<?php

namespace ProjectAnalyzer\Commands;

use Illuminate\Console\Command;
use ProjectAnalyzer\Engine\AnalysisEngine;
use ProjectAnalyzer\Testing\TestGenerationService;
use ProjectAnalyzer\Validation\ValidationService;

class TestSuggestionsCommand extends Command
{
    protected $signature = 'project:analyze:tests
        {--suggest : Show test suggestions}
        {--generate : Generate test stubs for missing coverage}
        {--force : Overwrite existing generated test files}
        {--framework= : Generate tests using pest or phpunit}
        {--base-path= : Base path to analyze and write tests into}';

    protected $description = 'Analyze test coverage and suggest missing tests';

    public function handle(
        AnalysisEngine $engine,
        TestGenerationService $testGenerationService,
        ValidationService $validationService
    ): int
    {
        $basePath = $this->option('base-path') ?: base_path();
        $framework = $this->option('framework') ?: (config('project-analyzer.test_generation.framework') ?? 'pest');
        $validation = $validationService->validateGenerationOptions([
            'framework' => $framework,
            'base_path' => $basePath,
        ]);

        if ($validation['failed'] > 0) {
            foreach ($validation['checks'] as $check) {
                if ($check['status'] === 'failed') {
                    $this->error($check['message']);
                }
            }

            return self::FAILURE;
        }

        $result = $engine->analyze([
            'analyzers' => 'test',
            'base_path' => $basePath,
        ]);
        $testData = $result->data['test'] ?? [];
        $missing = $testData['missing_tests'] ?? [];

        $this->info('Test Coverage: '.($testData['coverage_estimate'] ?? 0).'%');
        $this->info('Total Tests: '.($testData['total_tests'] ?? 0));

        if ($this->option('suggest')) {
            if (empty($missing)) {
                $this->info('No missing tests detected!');
            } else {
                $this->warn('Missing tests ('.count($missing).'):');
                foreach ($missing as $item) {
                    $this->line("  [{$item['type']}] {$item['class']} - {$item['suggestion']}");
                }
            }
        }

        if ($this->option('generate')) {
            if (empty($missing)) {
                $this->info('No test stubs generated because no missing tests were detected.');

                return self::SUCCESS;
            }

            $generationConfig = config('project-analyzer.test_generation', []);
            $generatedTests = $testGenerationService->buildSuggestions(
                $missing,
                $basePath,
                array_merge($generationConfig, ['framework' => $framework])
            );

            $results = $testGenerationService->writeFiles(
                $generatedTests,
                $basePath,
                $this->option('force') || (bool) ($generationConfig['overwrite'] ?? false)
            );

            $this->info('Generated test stubs: '.$results['written_count']);
            $this->info('Skipped existing files: '.$results['skipped_count']);

            foreach ($results['written'] as $path) {
                $this->line('  wrote: '.$path);
            }

            foreach ($results['skipped'] as $path) {
                $this->line('  skipped: '.$path);
            }
        }

        return self::SUCCESS;
    }
}
