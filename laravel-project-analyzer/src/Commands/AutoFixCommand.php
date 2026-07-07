<?php

namespace ProjectAnalyzer\Commands;

use Illuminate\Console\Command;
use ProjectAnalyzer\Engine\AnalysisEngine;
use ProjectAnalyzer\Fixes\AutoFixService;
use ProjectAnalyzer\Validation\ValidationService;

class AutoFixCommand extends Command
{
    protected $signature = 'project:analyze:fix
        {--apply : Apply supported fixes}
        {--force : Overwrite generated files where supported}
        {--base-path= : Base path to analyze and fix}';

    protected $description = 'List or apply supported auto-fixes for analyzer findings';

    public function handle(
        AnalysisEngine $engine,
        AutoFixService $autoFixService,
        ValidationService $validationService
    ): int
    {
        $basePath = $this->option('base-path') ?: base_path();
        $validation = $validationService->validateGenerationOptions([
            'framework' => config('project-analyzer.test_generation.framework', 'pest'),
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
            'base_path' => $basePath,
        ]);

        $candidates = $autoFixService->buildCandidates($result->data, $basePath);

        if (! $this->option('apply')) {
            $this->info('Supported auto-fix candidates: '.count($candidates));

            foreach ($candidates as $candidate) {
                $this->line("[{$candidate['category']}] {$candidate['title']} - ".($candidate['file'] ?? 'n/a'));
            }

            return self::SUCCESS;
        }

        $summary = $autoFixService->apply($candidates, $basePath, $this->option('force'));

        $this->info('Applied fixes: '.$summary['applied_count']);
        $this->info('Skipped fixes: '.$summary['skipped_count']);

        foreach ($summary['applied'] as $id) {
            $this->line('  applied: '.$id);
        }

        foreach ($summary['skipped'] as $id) {
            $this->line('  skipped: '.$id);
        }

        return self::SUCCESS;
    }
}
