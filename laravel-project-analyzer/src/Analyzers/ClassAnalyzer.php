<?php

namespace ProjectAnalyzer\Analyzers;

use ProjectAnalyzer\Analysis\Context;

class ClassAnalyzer extends AbstractAnalyzer
{
    public function getName(): string
    {
        return 'class';
    }

    public function analyze(Context $context): array
    {
        $classes = $context->classes;

        $byType = [];
        $byNamespace = [];

        foreach ($classes as $class) {
            $type = $class['type'] ?? 'class';
            $byType[$type] = ($byType[$type] ?? 0) + 1;

            $ns = $class['namespace'] ?? 'global';
            $byNamespace[$ns] = ($byNamespace[$ns] ?? 0) + 1;
        }

        return [
            'total' => count($classes),
            'by_type' => $byType,
            'by_namespace' => $byNamespace,
            'classes' => $classes,
        ];
    }
}
