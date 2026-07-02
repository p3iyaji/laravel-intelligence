<?php

namespace ProjectAnalyzer\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use ProjectAnalyzer\ProjectAnalyzerServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ProjectAnalyzerServiceProvider::class,
            \Inertia\ServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('project-analyzer.cache.enabled', false);
        $app['config']->set('project-analyzer.dashboard.enabled', true);
        $app['config']->set('project-analyzer.dashboard.middleware', ['web']);
        $app['config']->set('project-analyzer.analysis.paths', [
            'tests/Fixtures/app',
            'tests/Fixtures/database',
            'tests/Fixtures/routes',
            'tests/Fixtures/tests',
        ]);
        $app['config']->set('project-analyzer.export.location', sys_get_temp_dir().'/project-analysis-test');
    }

    protected function getFixturePath(string $path = ''): string
    {
        return __DIR__.'/Fixtures'.($path ? '/'.$path : '');
    }
}
