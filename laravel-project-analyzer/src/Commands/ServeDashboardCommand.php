<?php

namespace ProjectAnalyzer\Commands;

use Illuminate\Console\Command;

class ServeDashboardCommand extends Command
{
    protected $signature = 'project:analyze:dashboard {--port=8080 : Port to serve on}';

    protected $description = 'Display dashboard URL and instructions';

    public function handle(): int
    {
        $prefix = config('project-analyzer.dashboard.route_prefix', 'analyzer');
        $url = url($prefix);

        $this->info('Project Analyzer Dashboard');
        $this->line("URL: {$url}");
        $this->line('');
        $this->line('Make sure your Laravel application is running:');
        $this->line('  php artisan serve');
        $this->line('');
        $this->line('Then visit the dashboard URL above.');

        return self::SUCCESS;
    }
}
