<?php

use ProjectAnalyzer\Tests\TestCase;

uses(TestCase::class);

describe('RouteAnalyzer integration', function () {
    it('analyzes routes from fixture files', function () {
        $fixturePath = __DIR__.'/../Fixtures';
        $fileCollector = new \ProjectAnalyzer\Collectors\FileCollector($fixturePath, ['routes']);
        $files = $fileCollector->collect();
        $classCollector = new \ProjectAnalyzer\Collectors\ClassCollector($files);

        $context = new \ProjectAnalyzer\Analysis\Context(
            [],
            $fixturePath,
            files: $files,
            classes: $classCollector->collect(),
        );

        $result = (new \ProjectAnalyzer\Analyzers\RouteAnalyzer)->analyze($context);

        expect($result['total'])->toBeGreaterThanOrEqual(1);
        expect($result['routes'])->not->toBeEmpty();
    });
});

describe('ExampleAnalyzer plugin', function () {
    it('can be registered and executed', function () {
        $manager = app(\ProjectAnalyzer\Plugins\PluginManager::class);
        $analyzer = new \ProjectAnalyzer\Plugins\Examples\ExampleAnalyzer;
        $manager->register($analyzer);

        $fixturePath = __DIR__.'/../Fixtures';
        $engine = app(\ProjectAnalyzer\Engine\AnalysisEngine::class);
        $engine->registerAnalyzer($analyzer);

        $result = $engine->analyze(['base_path' => $fixturePath, 'analyzers' => 'example']);

        expect($result->data['example']['custom_metric'])->toBeGreaterThan(0);
    });
});

describe('Performance', function () {
    it('analyzes fixture project in under 2 seconds', function () {
        $fixturePath = __DIR__.'/../Fixtures';
        $this->app['config']->set('project-analyzer.cache.enabled', false);

        $start = microtime(true);
        $engine = app(\ProjectAnalyzer\Engine\AnalysisEngine::class);
        $result = $engine->analyze(['base_path' => $fixturePath]);
        $duration = microtime(true) - $start;

        expect($duration)->toBeLessThan(2.0);
        expect($result->metrics['statistics']['total_classes'])->toBeGreaterThan(0);
    });
});

describe('Additional Commands', function () {
    it('runs project:analyze:export command', function () {
        $this->artisan('project:analyze:export', ['--format' => 'json'])
            ->assertExitCode(0);
    });

    it('runs project:analyze:docs command', function () {
        $this->artisan('project:analyze:docs')
            ->assertExitCode(0);
    });

    it('runs project:analyze:report command', function () {
        $this->artisan('project:analyze:report', ['--format' => 'html'])
            ->assertExitCode(0);
    });
});

describe('AbstractAnalyzer helpers', function () {
    it('filters classes correctly', function () {
        $analyzer = new class extends \ProjectAnalyzer\Analyzers\AbstractAnalyzer
        {
            public function analyze(\ProjectAnalyzer\Analysis\Context $context): array
            {
                return [];
            }

            public function getName(): string
            {
                return 'test';
            }

            public function testFilter(array $classes): array
            {
                return $this->filterClasses($classes, fn ($c) => ($c['type'] ?? '') === 'class');
            }
        };

        $classes = [
            ['type' => 'class', 'fqn' => 'A'],
            ['type' => 'trait', 'fqn' => 'B'],
        ];

        expect($analyzer->testFilter($classes))->toHaveCount(1);
    });
});

describe('FileCollector edge cases', function () {
    it('falls back to base path when no paths exist', function () {
        $collector = new \ProjectAnalyzer\Collectors\FileCollector('/nonexistent', ['missing']);
        $files = $collector->collect();

        expect($files)->toBeArray();
    });
});

describe('AnalysisEngine cache', function () {
    it('uses cached results with quick option', function () {
        $fixturePath = __DIR__.'/../Fixtures';
        $this->app['config']->set('project-analyzer.cache.enabled', true);

        $engine = app(\ProjectAnalyzer\Engine\AnalysisEngine::class);
        $engine->analyze(['base_path' => $fixturePath]);
        $cached = $engine->analyze(['base_path' => $fixturePath, 'quick' => true]);

        expect($cached->metrics)->not->toBeEmpty();
    });
});
