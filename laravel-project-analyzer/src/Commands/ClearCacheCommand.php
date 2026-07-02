<?php

namespace ProjectAnalyzer\Commands;

use Illuminate\Console\Command;
use ProjectAnalyzer\Engine\AnalysisEngine;

class ClearCacheCommand extends Command
{
    protected $signature = 'project:analyze:clear';

    protected $description = 'Clear analysis cache';

    public function handle(AnalysisEngine $engine): int
    {
        $engine->clearCache();
        $this->info('Analysis cache cleared.');

        return self::SUCCESS;
    }
}
