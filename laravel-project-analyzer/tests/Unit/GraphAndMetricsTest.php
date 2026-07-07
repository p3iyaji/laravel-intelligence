<?php

use ProjectAnalyzer\Analysis\Context;
use ProjectAnalyzer\Collectors\ClassCollector;
use ProjectAnalyzer\Collectors\FileCollector;
use ProjectAnalyzer\Graph\DependencyGraphBuilder;
use ProjectAnalyzer\Graph\GraphVisualizer;
use ProjectAnalyzer\Graph\RelationshipMapper;
use ProjectAnalyzer\Metrics\ComplexityCalculator;
use ProjectAnalyzer\Metrics\CoverageCalculator;
use ProjectAnalyzer\Metrics\HealthScoreCalculator;

function createFullContext(): Context
{
    $fixturePath = __DIR__.'/../Fixtures';
    $fileCollector = new FileCollector($fixturePath, ['app', 'database', 'routes', 'tests']);
    $files = $fileCollector->collect();
    $classCollector = new ClassCollector($files);
    $classes = $classCollector->collect();

    $context = new Context([], $fixturePath, classes: $classes, files: $files);

    $modelAnalyzer = new \ProjectAnalyzer\Analyzers\ModelAnalyzer;
    $controllerAnalyzer = new \ProjectAnalyzer\Analyzers\ControllerAnalyzer;
    $databaseAnalyzer = new \ProjectAnalyzer\Analyzers\DatabaseAnalyzer;
    $serviceAnalyzer = new \ProjectAnalyzer\Analyzers\ServiceAnalyzer;
    $testAnalyzer = new \ProjectAnalyzer\Analyzers\TestAnalyzer;

    $context->addResult('model', $modelAnalyzer->analyze($context));
    $context->addResult('controller', $controllerAnalyzer->analyze($context));
    $context->addResult('database', $databaseAnalyzer->analyze($context));
    $context->addResult('service', $serviceAnalyzer->analyze($context));
    $context->addResult('test', $testAnalyzer->analyze($context));
    $context->addResult('security', (new \ProjectAnalyzer\Analyzers\SecurityAnalyzer)->analyze($context));
    $context->addResult('components', (new \ProjectAnalyzer\Collectors\ComponentCollector($classes))->collect());

    return $context;
}

describe('DependencyGraphBuilder', function () {
    it('builds dependency graph', function () {
        $context = createFullContext();
        $builder = new DependencyGraphBuilder;

        $graph = $builder->build($context);

        expect($graph)->toHaveKeys(['nodes', 'edges', 'node_count', 'edge_count', 'circular_dependencies']);
        expect($graph['node_count'])->toBeGreaterThan(0);
    });
});

describe('RelationshipMapper', function () {
    it('maps model and route relationships', function () {
        $context = createFullContext();
        $mapper = new RelationshipMapper;

        $relationships = $mapper->map($context);

        expect($relationships)->toHaveKeys(['model_to_table', 'model_relationships', 'route_to_controller']);
        expect($relationships['model_to_table'])->not->toBeEmpty();
    });
});

describe('CodeVisualizationService', function () {
    it('builds visualization datasets', function () {
        $context = createFullContext();
        $context->addResult('graph', (new DependencyGraphBuilder)->build($context));
        $service = new \ProjectAnalyzer\Graph\CodeVisualizationService;

        $visualizations = $service->build($context);

        expect($visualizations)->toHaveKeys([
            'component_breakdown',
            'namespace_breakdown',
            'dependency_hotspots',
            'class_size_heatmap',
            'route_activity',
        ]);
        expect($visualizations['component_breakdown'])->not->toBeEmpty();
    });
});

describe('GraphVisualizer', function () {
    it('generates mermaid diagram', function () {
        $context = createFullContext();
        $builder = new DependencyGraphBuilder;
        $graph = $builder->build($context);
        $visualizer = new GraphVisualizer;

        $mermaid = $visualizer->toMermaid($graph);

        expect($mermaid)->toStartWith('graph TD');
        expect($mermaid)->toContain('-->');
    });

    it('generates er diagram', function () {
        $context = createFullContext();
        $mapper = new RelationshipMapper;
        $relationships = $mapper->map($context);
        $visualizer = new GraphVisualizer;

        $er = $visualizer->toErDiagram($relationships);

        expect($er)->toStartWith('erDiagram');
    });

    it('generates class diagram', function () {
        $context = createFullContext();
        $class = collect($context->classes)->firstWhere('fqn', 'App\\Http\\Controllers\\UserController');
        $visualizer = new GraphVisualizer;

        $diagram = $visualizer->toClassDiagram($class);

        expect($diagram)->toStartWith('classDiagram');
        expect($diagram)->toContain('UserController');
        expect($diagram)->toContain('index');
    });

    it('generates dependency subgraph', function () {
        $context = createFullContext();
        $builder = new DependencyGraphBuilder;
        $graph = $builder->build($context);
        $visualizer = new GraphVisualizer;

        $subgraph = $visualizer->toSubgraph($graph, 'App\\Http\\Controllers\\UserController');

        expect($subgraph)->toStartWith('graph TD');
        expect($subgraph)->toContain('UserController');
    });
});

describe('ComplexityCalculator', function () {
    it('calculates complexity metrics', function () {
        $context = createFullContext();
        $calculator = new ComplexityCalculator;

        $result = $calculator->calculate($context);

        expect($result)->toHaveKeys(['average_complexity', 'total_methods', 'largest_classes']);
        expect($result['total_methods'])->toBeGreaterThan(0);
    });
});

describe('CoverageCalculator', function () {
    it('calculates coverage metrics', function () {
        $context = createFullContext();
        $calculator = new CoverageCalculator;

        $result = $calculator->calculate($context);

        expect($result)->toHaveKeys(['overall', 'by_type', 'missing_tests']);
    });
});

describe('HealthScoreCalculator', function () {
    it('calculates health scores', function () {
        $context = createFullContext();
        $calculator = new HealthScoreCalculator(
            new ComplexityCalculator,
            new CoverageCalculator,
            new DependencyGraphBuilder,
        );

        $result = $calculator->calculate($context);

        expect($result)->toHaveKeys(['overall', 'testability', 'code_quality', 'architecture', 'security', 'maintainability']);
        expect($result['overall'])->toBeGreaterThanOrEqual(0);
        expect($result['overall'])->toBeLessThanOrEqual(100);
    });
});
