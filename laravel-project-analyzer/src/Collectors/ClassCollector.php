<?php

namespace ProjectAnalyzer\Collectors;

use PhpParser\Node\Stmt\Namespace_;
use ProjectAnalyzer\Contracts\CollectorInterface;
use ProjectAnalyzer\Parsing\ClassVisitor;
use ProjectAnalyzer\Parsing\PhpParserFactory;

class ClassCollector implements CollectorInterface
{
    /**
     * @param  array<int, array<string, mixed>>  $files
     */
    public function __construct(
        private readonly array $files = [],
    ) {}

    public function collect(): array
    {
        $classes = [];

        foreach ($this->files as $file) {
            $path = $file['absolute_path'] ?? $file['path'] ?? null;
            if (! $path || ! file_exists($path)) {
                continue;
            }

            $ast = PhpParserFactory::parseFile($path);
            if ($ast === null) {
                continue;
            }

            $namespace = '';
            foreach ($ast as $node) {
                if ($node instanceof Namespace_) {
                    $namespace = $node->name?->toString() ?? '';
                    break;
                }
            }

            $visitor = new ClassVisitor($file['path'] ?? $path, $namespace);
            $traverser = PhpParserFactory::createTraverser();
            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);

            foreach ($visitor->getClasses() as $class) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    public function getName(): string
    {
        return 'class';
    }
}
