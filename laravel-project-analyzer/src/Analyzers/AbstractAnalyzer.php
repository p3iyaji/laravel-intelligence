<?php

namespace ProjectAnalyzer\Analyzers;

use ProjectAnalyzer\Analysis\Context;
use ProjectAnalyzer\Contracts\AnalyzerInterface;

abstract class AbstractAnalyzer implements AnalyzerInterface
{
    protected bool $enabled = true;

    protected int $priority = 50;

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @param  array<int, array<string, mixed>>  $classes
     * @return array<int, array<string, mixed>>
     */
    protected function filterClasses(array $classes, callable $filter): array
    {
        return array_values(array_filter($classes, $filter));
    }

    /**
     * @param  array<string, mixed>  $class
     */
    protected function classExtends(array $class, string $parent): bool
    {
        $extends = $class['extends'] ?? '';

        return str_contains((string) $extends, $parent);
    }

    /**
     * @param  array<string, mixed>  $class
     */
    protected function classInPath(array $class, string $path): bool
    {
        return str_contains($class['file'] ?? '', $path);
    }

    abstract public function analyze(Context $context): array;

    abstract public function getName(): string;
}
