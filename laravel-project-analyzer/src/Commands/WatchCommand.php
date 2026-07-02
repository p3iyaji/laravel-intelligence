<?php

namespace ProjectAnalyzer\Commands;

use Illuminate\Console\Command;
use ProjectAnalyzer\Engine\AnalysisEngine;

class WatchCommand extends Command
{
    protected $signature = 'project:analyze:watch {--interval=30 : Watch interval in seconds}';

    protected $description = 'Watch for file changes and re-run analysis';

    public function handle(AnalysisEngine $engine): int
    {
        $interval = (int) $this->option('interval');
        $paths = config('project-analyzer.analysis.paths', ['app']);
        $lastMtime = 0;

        $this->info("Watching for changes (interval: {$interval}s)... Press Ctrl+C to stop.");

        while (true) {
            $currentMtime = $this->getLatestMtime($paths);

            if ($currentMtime > $lastMtime && $lastMtime > 0) {
                $this->info('Changes detected, re-running analysis...');
                $engine->clearCache();
                $result = $engine->analyze();
                $this->line('Health: '.($result->metrics['overall'] ?? 'N/A'));
            }

            $lastMtime = $currentMtime;
            sleep($interval);
        }
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function getLatestMtime(array $paths): int
    {
        $latest = 0;

        foreach ($paths as $path) {
            $fullPath = base_path($path);
            if (! is_dir($fullPath)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
                    $latest = max($latest, $file->getMTime());
                }
            }
        }

        return $latest;
    }
}
