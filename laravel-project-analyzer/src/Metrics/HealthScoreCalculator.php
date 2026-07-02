<?php

namespace ProjectAnalyzer\Metrics;

use ProjectAnalyzer\Analysis\Context;
use ProjectAnalyzer\Graph\DependencyGraphBuilder;
use ProjectAnalyzer\Metrics\ComplexityCalculator;
use ProjectAnalyzer\Metrics\CoverageCalculator;

class HealthScoreCalculator
{
    public function __construct(
        private readonly ComplexityCalculator $complexityCalculator,
        private readonly CoverageCalculator $coverageCalculator,
        private readonly DependencyGraphBuilder $graphBuilder,
    ) {}

    public function calculate(Context $context): array
    {
        $complexity = $this->complexityCalculator->calculate($context);
        $coverage = $this->coverageCalculator->calculate($context);
        $graph = $this->graphBuilder->build($context);

        $components = $context->getResult('components') ?? [];
        $security = $context->getResult('security') ?? [];

        $testScore = min(100, $coverage['overall'] ?? 0);
        $complexityScore = max(0, 100 - (($complexity['average_complexity'] ?? 0) * 2));
        $architectureScore = max(0, 100 - (count($graph['circular_dependencies'] ?? []) * 10));
        $securityScore = max(0, 100 - (($security['high_severity'] ?? 0) * 20) - (($security['medium_severity'] ?? 0) * 5));
        $maintainabilityScore = round(($testScore + $complexityScore + $architectureScore) / 3, 2);

        $overall = round(($testScore * 0.3 + $complexityScore * 0.2 + $architectureScore * 0.2 + $securityScore * 0.15 + $maintainabilityScore * 0.15), 2);

        return [
            'overall' => $overall,
            'testability' => $testScore,
            'code_quality' => $complexityScore,
            'architecture' => $architectureScore,
            'security' => $securityScore,
            'maintainability' => $maintainabilityScore,
            'statistics' => [
                'total_classes' => count($context->classes),
                'total_files' => count($context->files),
                'total_models' => count($components['models'] ?? []),
                'total_controllers' => count($components['controllers'] ?? []),
                'total_services' => count($components['services'] ?? []),
                'total_tests' => $coverage['total_tests'] ?? 0,
                'total_routes' => $context->getResult('route')['total'] ?? 0,
                'total_migrations' => $context->getResult('database')['total_migrations'] ?? 0,
            ],
            'complexity' => $complexity,
            'coverage' => $coverage,
            'graph_summary' => [
                'nodes' => $graph['node_count'] ?? 0,
                'edges' => $graph['edge_count'] ?? 0,
                'circular_dependencies' => count($graph['circular_dependencies'] ?? []),
            ],
        ];
    }
}
