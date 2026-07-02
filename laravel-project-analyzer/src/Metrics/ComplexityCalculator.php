<?php

namespace ProjectAnalyzer\Metrics;

use ProjectAnalyzer\Analysis\Context;

class ComplexityCalculator
{
    public function calculate(Context $context): array
    {
        $complexities = [];
        $totalMethods = 0;
        $totalComplexity = 0;

        foreach ($context->classes as $class) {
            $methodCount = $class['method_count'] ?? 0;
            $complexity = $this->estimateClassComplexity($class);

            $complexities[] = [
                'class' => $class['fqn'] ?? '',
                'method_count' => $methodCount,
                'complexity' => $complexity,
            ];

            $totalMethods += $methodCount;
            $totalComplexity += $complexity;
        }

        usort($complexities, fn ($a, $b) => $b['complexity'] <=> $a['complexity']);

        return [
            'average_complexity' => $totalMethods > 0 ? round($totalComplexity / count($context->classes ?: [1]), 2) : 0,
            'total_methods' => $totalMethods,
            'largest_classes' => array_slice($complexities, 0, 10),
            'high_complexity_classes' => array_filter($complexities, fn ($c) => $c['complexity'] > 20),
        ];
    }

    /**
     * @param  array<string, mixed>  $class
     */
    private function estimateClassComplexity(array $class): int
    {
        $base = ($class['method_count'] ?? 0) * 2;
        $base += count($class['implements'] ?? []) * 3;
        $base += count($class['traits'] ?? []) * 2;

        if ($class['is_abstract'] ?? false) {
            $base += 5;
        }

        return $base;
    }
}
