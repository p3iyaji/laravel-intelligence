<?php

namespace ProjectAnalyzer\Analyzers;

use ProjectAnalyzer\Analysis\Context;

class TestAnalyzer extends AbstractAnalyzer
{
    public function getName(): string
    {
        return 'test';
    }

    public function analyze(Context $context): array
    {
        $tests = $this->filterClasses(
            $context->classes,
            fn ($c) => str_contains($c['file'] ?? '', 'tests/')
                || str_contains($c['file'] ?? '', 'Tests/')
                || str_contains($c['file'] ?? '', 'Unit/')
                || str_contains($c['file'] ?? '', 'Feature/')
                || str_ends_with($c['name'] ?? '', 'Test')
        );

        $components = $context->getResult('components') ?? [];
        $controllers = $components['controllers'] ?? [];
        $models = $components['models'] ?? [];
        $services = $components['services'] ?? [];

        $testedClasses = $this->findTestedClasses($tests);
        $missingTests = $this->findMissingTests($controllers, $models, $services, $testedClasses);

        return [
            'total_tests' => count($tests),
            'tests' => array_map(fn ($t) => [
                'fqn' => $t['fqn'],
                'file' => $t['file'],
            ], $tests),
            'tested_classes' => $testedClasses,
            'missing_tests' => $missingTests,
            'coverage_estimate' => $this->estimateCoverage($controllers, $models, $services, $testedClasses),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $tests
     * @return array<int, string>
     */
    private function findTestedClasses(array $tests): array
    {
        $tested = [];

        foreach ($tests as $test) {
            $name = $test['name'] ?? '';
            $tested[] = str_replace('Test', '', $name);
        }

        return array_unique($tested);
    }

    /**
     * @param  array<int, array<string, mixed>>  $controllers
     * @param  array<int, array<string, mixed>>  $models
     * @param  array<int, array<string, mixed>>  $services
     * @param  array<int, string>  $testedClasses
     * @return array<int, array<string, string>>
     */
    private function findMissingTests(array $controllers, array $models, array $services, array $testedClasses): array
    {
        $missing = [];

        foreach (array_merge($controllers, $models, $services) as $component) {
            $name = $component['name'] ?? '';
            if (! in_array($name, $testedClasses, true)) {
                $missing[] = [
                    'class' => $component['fqn'] ?? $name,
                    'type' => str_contains($component['file'] ?? '', 'Controller') ? 'controller' : (
                        str_contains($component['file'] ?? '', 'Models') ? 'model' : 'service'
                    ),
                    'suggestion' => "Create test for {$name}",
                ];
            }
        }

        return $missing;
    }

    /**
     * @param  array<int, array<string, mixed>>  $controllers
     * @param  array<int, array<string, mixed>>  $models
     * @param  array<int, array<string, mixed>>  $services
     * @param  array<int, string>  $testedClasses
     */
    private function estimateCoverage(array $controllers, array $models, array $services, array $testedClasses): float
    {
        $total = count($controllers) + count($models) + count($services);
        if ($total === 0) {
            return 100.0;
        }

        $tested = 0;
        foreach (array_merge($controllers, $models, $services) as $component) {
            if (in_array($component['name'] ?? '', $testedClasses, true)) {
                $tested++;
            }
        }

        return round(($tested / $total) * 100, 2);
    }
}
