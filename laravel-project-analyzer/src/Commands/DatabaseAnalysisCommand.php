<?php

namespace ProjectAnalyzer\Commands;

use Illuminate\Console\Command;
use ProjectAnalyzer\Engine\AnalysisEngine;

class DatabaseAnalysisCommand extends Command
{
    protected $signature = 'project:analyze:database';

    protected $description = 'Run database schema analysis';

    public function handle(AnalysisEngine $engine): int
    {
        $result = $engine->analyze(['analyzers' => 'database']);
        $data = $result->data['database'] ?? [];

        $this->table(['Metric', 'Value'], [
            ['Migrations', $data['total_migrations'] ?? 0],
            ['Tables', $data['total_tables'] ?? 0],
        ]);

        if (! empty($data['tables'])) {
            $this->info('Tables:');
            foreach ($data['tables'] as $table) {
                $this->line("  - {$table}");
            }
        }

        return self::SUCCESS;
    }
}
