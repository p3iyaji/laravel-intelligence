<?php

namespace ProjectAnalyzer\Plugins\Examples;

use ProjectAnalyzer\Analysis\Context;
use ProjectAnalyzer\Contracts\AnalyzerInterface;

/**
 * Example custom analyzer plugin.
 * Register via: Analyzer::register(new ExampleAnalyzer());
 */
class ExampleAnalyzer implements AnalyzerInterface
{
    public function analyze(Context $context): array
    {
        return [
            'custom_metric' => count($context->classes),
            'findings' => [
                [
                    'message' => 'Example plugin analysis complete',
                    'class_count' => count($context->classes),
                ],
            ],
        ];
    }

    public function getName(): string
    {
        return 'example';
    }

    public function getPriority(): int
    {
        return 100;
    }

    public function isEnabled(): bool
    {
        return true;
    }
}
