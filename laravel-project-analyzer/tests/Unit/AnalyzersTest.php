<?php

use ProjectAnalyzer\Analyzers\ClassAnalyzer;
use ProjectAnalyzer\Analyzers\ControllerAnalyzer;
use ProjectAnalyzer\Analyzers\DatabaseAnalyzer;
use ProjectAnalyzer\Analyzers\ModelAnalyzer;
use ProjectAnalyzer\Analyzers\RouteAnalyzer;
use ProjectAnalyzer\Analyzers\SecurityAnalyzer;
use ProjectAnalyzer\Analyzers\ServiceAnalyzer;
use ProjectAnalyzer\Analyzers\TestAnalyzer;
use ProjectAnalyzer\Analysis\Context;
use ProjectAnalyzer\Collectors\ClassCollector;
use ProjectAnalyzer\Collectors\FileCollector;

function createTestContext(): Context
{
    $fixturePath = __DIR__.'/../Fixtures';
    $fileCollector = new FileCollector($fixturePath, ['app', 'database', 'routes', 'tests']);
    $files = $fileCollector->collect();
    $classCollector = new ClassCollector($files);
    $classes = $classCollector->collect();

    return new Context(
        config: [],
        basePath: $fixturePath,
        paths: ['app', 'database', 'routes', 'tests'],
        classes: $classes,
        files: $files,
    );
}

describe('ClassAnalyzer', function () {
    it('analyzes all classes', function () {
        $context = createTestContext();
        $analyzer = new ClassAnalyzer;

        $result = $analyzer->analyze($context);

        expect($result['total'])->toBeGreaterThan(0);
        expect($result)->toHaveKeys(['by_type', 'by_namespace', 'classes']);
        expect($analyzer->getName())->toBe('class');
        expect($analyzer->isEnabled())->toBeTrue();
    });
});

describe('ModelAnalyzer', function () {
    it('finds models and relationships', function () {
        $context = createTestContext();
        $analyzer = new ModelAnalyzer;

        $result = $analyzer->analyze($context);

        expect($result['total'])->toBeGreaterThanOrEqual(2);
        expect($result['models'])->not->toBeEmpty();

        $user = collect($result['models'])->firstWhere('fqn', 'App\Models\User');
        expect($user)->not->toBeNull();
        expect($user['table'])->toBe('users');
    });
});

describe('ControllerAnalyzer', function () {
    it('finds controllers and methods', function () {
        $context = createTestContext();
        $analyzer = new ControllerAnalyzer;

        $result = $analyzer->analyze($context);

        expect($result['total'])->toBeGreaterThanOrEqual(1);
        expect($result['controllers'][0]['fqn'])->toBe('App\Http\Controllers\UserController');
    });
});

describe('DatabaseAnalyzer', function () {
    it('analyzes migrations', function () {
        $context = createTestContext();
        $analyzer = new DatabaseAnalyzer;

        $result = $analyzer->analyze($context);

        expect($result['total_migrations'])->toBeGreaterThanOrEqual(1);
        expect($result['tables'])->toContain('users');
        expect($result['tables'])->toContain('posts');
    });
});

describe('ServiceAnalyzer', function () {
    it('finds services and repositories', function () {
        $context = createTestContext();
        $analyzer = new ServiceAnalyzer;

        $result = $analyzer->analyze($context);

        expect($result['total_services'])->toBeGreaterThanOrEqual(1);
        expect($result['total_repositories'])->toBeGreaterThanOrEqual(1);
    });
});

describe('TestAnalyzer', function () {
    it('calculates test coverage', function () {
        $context = createTestContext();
        $analyzer = new TestAnalyzer;

        $result = $analyzer->analyze($context);

        expect($result)->toHaveKeys(['total_tests', 'coverage_estimate', 'missing_tests']);
        expect($result['total_tests'])->toBeGreaterThanOrEqual(1);
    });
});

describe('SecurityAnalyzer', function () {
    it('runs security scan', function () {
        $context = createTestContext();
        $analyzer = new SecurityAnalyzer;

        $result = $analyzer->analyze($context);

        expect($result)->toHaveKeys(['total_findings', 'findings', 'high_severity']);
    });
});

describe('RouteAnalyzer', function () {
    it('has correct name and priority', function () {
        $analyzer = new RouteAnalyzer;
        expect($analyzer->getName())->toBe('route');
        expect($analyzer->getPriority())->toBe(50);
    });
});
