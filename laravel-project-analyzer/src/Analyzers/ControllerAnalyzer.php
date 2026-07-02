<?php

namespace ProjectAnalyzer\Analyzers;

use ProjectAnalyzer\Analysis\Context;

class ControllerAnalyzer extends AbstractAnalyzer
{
    public function getName(): string
    {
        return 'controller';
    }

    public function analyze(Context $context): array
    {
        $controllers = $this->filterClasses(
            $context->classes,
            fn ($c) => str_ends_with($c['fqn'] ?? '', 'Controller') || $this->classInPath($c, '/Controllers/')
        );

        $analyzed = [];

        foreach ($controllers as $controller) {
            $publicMethods = array_filter(
                $controller['methods'] ?? [],
                fn ($m) => ($m['visibility'] ?? '') === 'public' && ! str_starts_with($m['name'] ?? '', '__')
            );

            $analyzed[] = [
                'fqn' => $controller['fqn'],
                'file' => $controller['file'],
                'methods' => array_values($publicMethods),
                'method_count' => count($publicMethods),
                'dependencies' => $this->extractConstructorDependencies($controller),
            ];
        }

        return [
            'total' => count($analyzed),
            'controllers' => $analyzed,
        ];
    }

    /**
     * @param  array<string, mixed>  $controller
     * @return array<int, string>
     */
    private function extractConstructorDependencies(array $controller): array
    {
        foreach ($controller['methods'] ?? [] as $method) {
            if (($method['name'] ?? '') === '__construct') {
                return ['constructor_injection_detected' => $method['parameters'] > 0];
            }
        }

        return [];
    }
}
