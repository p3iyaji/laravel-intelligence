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
                $type = $this->detectComponentType($component);
                $missing[] = [
                    'class' => $component['fqn'] ?? $name,
                    'name' => $name,
                    'type' => $type,
                    'suggestion' => "Create test for {$name}",
                    'source_file' => $component['file'] ?? null,
                    'suggested_suite' => $type === 'controller' ? 'feature' : 'unit',
                    'suggested_path' => ($type === 'controller' ? 'tests/Feature/' : 'tests/Unit/').$name.'Test.php',
                    'public_methods' => $this->extractPublicMethods($component),
                    'suggested_cases' => $this->suggestedCasesForComponent($component, $type),
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

    /**
     * @param  array<string, mixed>  $component
     * @return array<int, string>
     */
    private function extractPublicMethods(array $component): array
    {
        $methods = [];

        foreach ($component['methods'] ?? [] as $method) {
            $name = $method['name'] ?? null;
            if ($name === null || str_starts_with($name, '__')) {
                continue;
            }

            if (($method['visibility'] ?? 'public') === 'public') {
                $methods[] = $name;
            }
        }

        return $methods;
    }

    /**
     * @param  array<string, mixed>  $component
     */
    private function detectComponentType(array $component): string
    {
        $file = (string) ($component['file'] ?? '');

        if (str_contains($file, 'Controller') || str_contains($file, '/Controllers/')) {
            return 'controller';
        }

        if (str_contains($file, 'Models') || str_contains($file, '/Models/')) {
            return 'model';
        }

        return 'service';
    }

    /**
     * @param  array<string, mixed>  $component
     * @return array<int, string>
     */
    private function suggestedCasesForComponent(array $component, string $type): array
    {
        $cases = [];
        $name = (string) ($component['name'] ?? 'component');
        $publicMethods = $this->extractPublicMethods($component);

        if ($type === 'controller') {
            $cases[] = 'add request/response assertions for '.$name;
            $cases[] = 'cover authorization and validation behavior';
        } elseif ($type === 'model') {
            $cases[] = 'cover relationships and attribute behavior';
            $cases[] = 'assert casts, scopes, and fillable/guarded expectations';
        } else {
            $cases[] = 'cover the main business flow and edge cases';
            $cases[] = 'mock dependencies for isolated service behavior';
        }

        foreach ($publicMethods as $method) {
            $cases[] = 'add coverage for '.$name.'::'.$method;
        }

        return array_values(array_unique($cases));
    }
}
