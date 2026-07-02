<?php

namespace ProjectAnalyzer\Metrics;

use ProjectAnalyzer\Analysis\Context;

class CoverageCalculator
{
    public function calculate(Context $context): array
    {
        $testData = $context->getResult('test') ?? [];
        $components = $context->getResult('components') ?? [];

        $coverage = $testData['coverage_estimate'] ?? 0;
        $missing = $testData['missing_tests'] ?? [];

        $byType = [
            'controllers' => $this->coverageForType($components['controllers'] ?? [], $missing, 'controller'),
            'models' => $this->coverageForType($components['models'] ?? [], $missing, 'model'),
            'services' => $this->coverageForType($components['services'] ?? [], $missing, 'service'),
        ];

        return [
            'overall' => $coverage,
            'by_type' => $byType,
            'total_tests' => $testData['total_tests'] ?? 0,
            'missing_tests' => $missing,
            'missing_count' => count($missing),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $components
     * @param  array<int, array<string, mixed>>  $missing
     */
    private function coverageForType(array $components, array $missing, string $type): float
    {
        $total = count($components);
        if ($total === 0) {
            return 100.0;
        }

        $missingForType = count(array_filter($missing, fn ($m) => ($m['type'] ?? '') === $type));

        return round((($total - $missingForType) / $total) * 100, 2);
    }
}
