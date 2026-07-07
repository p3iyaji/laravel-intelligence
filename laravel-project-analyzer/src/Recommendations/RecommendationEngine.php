<?php

namespace ProjectAnalyzer\Recommendations;

use ProjectAnalyzer\Analysis\Context;

class RecommendationEngine
{
    public function generate(Context $context): array
    {
        $recommendations = [];

        $testData = $context->getResult('test') ?? [];
        foreach ($testData['missing_tests'] ?? [] as $missing) {
            $recommendations[] = [
                'category' => 'testing',
                'priority' => 'high',
                'title' => 'Add test coverage',
                'description' => $missing['suggestion'] ?? 'Create missing test',
                'class' => $missing['class'] ?? null,
                'impact' => 'Improves reliability and maintainability',
            ];
        }

        $security = $context->getResult('security') ?? [];
        foreach ($security['findings'] ?? [] as $finding) {
            $recommendations[] = [
                'category' => 'security',
                'priority' => $finding['severity'] ?? 'medium',
                'title' => 'Security issue detected',
                'description' => $finding['message'] ?? '',
                'file' => $finding['file'] ?? null,
                'impact' => 'May expose application to security vulnerabilities',
            ];
        }

        $costData = $context->getResult('cost') ?? [];
        foreach ($costData['hotspots'] ?? [] as $hotspot) {
            $recommendations[] = [
                'category' => 'cost',
                'priority' => $hotspot['severity'] ?? 'medium',
                'title' => 'Potential runtime cost hotspot',
                'description' => $hotspot['message'] ?? '',
                'file' => $hotspot['file'] ?? null,
                'estimated_cost' => $hotspot['estimated_cost'] ?? null,
                'impact' => 'May increase query, memory, or network cost during runtime',
            ];
        }

        $serviceData = $context->getResult('service') ?? [];
        foreach ($serviceData['services'] ?? [] as $service) {
            if (! ($service['has_interface'] ?? false) && ($service['method_count'] ?? 0) > 5) {
                $recommendations[] = [
                    'category' => 'enhancement',
                    'priority' => 'medium',
                    'title' => 'Add interface abstraction',
                    'description' => "Consider adding an interface for {$service['fqn']}",
                    'class' => $service['fqn'],
                    'impact' => 'Improves testability and follows SOLID principles',
                ];
            }

            if (($service['method_count'] ?? 0) > 8) {
                $recommendations[] = [
                    'category' => 'enhancement',
                    'priority' => 'medium',
                    'title' => 'Split large service responsibilities',
                    'description' => "Break {$service['fqn']} into smaller focused services or actions.",
                    'class' => $service['fqn'],
                    'impact' => 'Reduces change risk and improves maintainability',
                ];
            }
        }

        $controllerData = $context->getResult('controller') ?? [];
        foreach ($controllerData['controllers'] ?? [] as $controller) {
            if (($controller['method_count'] ?? 0) > 5) {
                $recommendations[] = [
                    'category' => 'enhancement',
                    'priority' => 'medium',
                    'title' => 'Slim down controller actions',
                    'description' => "Consider extracting responsibilities from {$controller['fqn']}.",
                    'class' => $controller['fqn'],
                    'impact' => 'Improves readability and keeps HTTP endpoints thin',
                ];
            }
        }

        $modelData = $context->getResult('model') ?? [];
        foreach ($modelData['models'] ?? [] as $model) {
            if (count($model['relationships'] ?? []) > 4) {
                $recommendations[] = [
                    'category' => 'enhancement',
                    'priority' => 'low',
                    'title' => 'Review complex model responsibilities',
                    'description' => "Model {$model['fqn']} has many relationships and may benefit from supporting query or domain classes.",
                    'class' => $model['fqn'],
                    'impact' => 'Can simplify rich domain models as they grow',
                ];
            }
        }

        $complexity = $context->getResult('metrics')['complexity'] ?? null;
        if ($complexity === null) {
            $calc = new \ProjectAnalyzer\Metrics\ComplexityCalculator;
            $complexity = $calc->calculate($context);
        }

        foreach ($complexity['high_complexity_classes'] ?? [] as $class) {
            $recommendations[] = [
                'category' => 'refactoring',
                'priority' => 'medium',
                'title' => 'High complexity class',
                'description' => "Refactor {$class['class']} (complexity: {$class['complexity']})",
                'class' => $class['class'],
                'impact' => 'Reduces technical debt and improves maintainability',
            ];
        }

        usort($recommendations, fn ($a, $b) => $this->priorityWeight($b['priority']) <=> $this->priorityWeight($a['priority']));

        return $recommendations;
    }

    private function priorityWeight(string $priority): int
    {
        return match ($priority) {
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0,
        };
    }
}
