<?php

namespace ProjectAnalyzer\Commands;

use Illuminate\Console\Command;
use ProjectAnalyzer\Validation\ValidationService;

class ValidateCommand extends Command
{
    protected $signature = 'project:analyze:validate {--base-path= : Base path to validate}';

    protected $description = 'Validate analyzer configuration, dashboard assets, and writable paths';

    public function handle(ValidationService $validationService): int
    {
        $basePath = $this->option('base-path') ?: base_path();
        $result = $validationService->validateEnvironment($basePath, config('project-analyzer', []));

        $this->table(['Check', 'Status', 'Message'], array_map(
            fn ($check) => [$check['name'], $check['status'], $check['message']],
            $result['checks']
        ));

        $this->info('Passed: '.$result['passed']);
        $this->warn('Warnings: '.$result['warnings']);

        if ($result['failed'] > 0) {
            $this->error('Failed: '.$result['failed']);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
