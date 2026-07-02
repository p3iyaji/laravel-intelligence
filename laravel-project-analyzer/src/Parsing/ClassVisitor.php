<?php

namespace ProjectAnalyzer\Parsing;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;

class ClassVisitor extends NodeVisitorAbstract
{
    /** @var array<int, array<string, mixed>> */
    private array $classes = [];

    public function __construct(
        private readonly string $filePath,
        private readonly string $namespace = '',
    ) {}

    public function enterNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->classes[] = $this->extractClassInfo($node, 'class');
        } elseif ($node instanceof Interface_) {
            $this->classes[] = $this->extractClassInfo($node, 'interface');
        } elseif ($node instanceof Trait_) {
            $this->classes[] = $this->extractClassInfo($node, 'trait');
        } elseif ($node instanceof Enum_) {
            $this->classes[] = $this->extractClassInfo($node, 'enum');
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractClassInfo(Class_|Interface_|Trait_|Enum_ $node, string $type): array
    {
        $name = $node->name?->toString() ?? 'anonymous';
        $fqn = $this->namespace ? $this->namespace.'\\'.$name : $name;

        $methods = [];
        foreach ($node->getMethods() as $method) {
            if ($method->name->toString() === '__construct' || $method->isPublic()) {
                $methods[] = [
                    'name' => $method->name->toString(),
                    'visibility' => $this->getVisibility($method),
                    'parameters' => count($method->params),
                    'return_type' => $method->getReturnType()?->toString(),
                    'is_static' => $method->isStatic(),
                ];
            }
        }

        $extends = null;
        if ($node instanceof Class_ && $node->extends) {
            $extends = $node->extends->toString();
        }

        $implements = [];
        if ($node instanceof Class_ || $node instanceof Enum_) {
            foreach ($node->implements ?? [] as $interface) {
                $implements[] = $interface->toString();
            }
        }

        $traits = [];
        if ($node instanceof Class_) {
            foreach ($node->getTraitUses() as $traitUse) {
                foreach ($traitUse->traits as $trait) {
                    $traits[] = $trait->toString();
                }
            }
        }

        return [
            'name' => $name,
            'fqn' => $fqn,
            'type' => $type,
            'file' => $this->filePath,
            'namespace' => $this->namespace,
            'extends' => $extends,
            'implements' => $implements,
            'traits' => $traits,
            'methods' => $methods,
            'method_count' => count($methods),
            'is_abstract' => $node instanceof Class_ && $node->isAbstract(),
            'is_final' => $node instanceof Class_ && $node->isFinal(),
        ];
    }

    private function getVisibility(\PhpParser\Node\Stmt\ClassMethod $method): string
    {
        if ($method->isPublic()) {
            return 'public';
        }
        if ($method->isProtected()) {
            return 'protected';
        }

        return 'private';
    }
}
