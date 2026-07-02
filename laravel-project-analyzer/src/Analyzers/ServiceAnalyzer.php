<?php

namespace ProjectAnalyzer\Analyzers;

use ProjectAnalyzer\Analysis\Context;

class ServiceAnalyzer extends AbstractAnalyzer
{
    public function getName(): string
    {
        return 'service';
    }

    public function analyze(Context $context): array
    {
        $services = $this->filterClasses(
            $context->classes,
            fn ($c) => $this->classInPath($c, '/Services/') || str_ends_with($c['fqn'] ?? '', 'Service')
        );

        $repositories = $this->filterClasses(
            $context->classes,
            fn ($c) => $this->classInPath($c, '/Repositories/') || str_ends_with($c['fqn'] ?? '', 'Repository')
        );

        $analyzed = [];

        foreach ($services as $service) {
            $analyzed[] = [
                'fqn' => $service['fqn'],
                'file' => $service['file'],
                'methods' => $service['methods'] ?? [],
                'method_count' => $service['method_count'] ?? 0,
                'implements' => $service['implements'] ?? [],
                'has_interface' => ! empty($service['implements']),
            ];
        }

        return [
            'total_services' => count($analyzed),
            'total_repositories' => count($repositories),
            'services' => $analyzed,
            'repositories' => array_map(fn ($r) => [
                'fqn' => $r['fqn'],
                'file' => $r['file'],
                'methods' => $r['methods'] ?? [],
            ], $repositories),
        ];
    }
}
