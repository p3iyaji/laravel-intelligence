<?php

namespace ProjectAnalyzer\Commands;

use Illuminate\Console\Command;
use ProjectAnalyzer\Engine\AnalysisEngine;

class SecurityAuditCommand extends Command
{
    protected $signature = 'project:analyze:security';

    protected $description = 'Run security audit on the project';

    public function handle(AnalysisEngine $engine): int
    {
        $result = $engine->analyze(['analyzers' => 'security']);
        $data = $result->data['security'] ?? [];

        $this->table(['Severity', 'Count'], [
            ['High', $data['high_severity'] ?? 0],
            ['Medium', $data['medium_severity'] ?? 0],
            ['Low', $data['low_severity'] ?? 0],
        ]);

        foreach ($data['findings'] ?? [] as $finding) {
            $this->line("[{$finding['severity']}] {$finding['message']} ({$finding['file']})");
        }

        return ($data['high_severity'] ?? 0) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
