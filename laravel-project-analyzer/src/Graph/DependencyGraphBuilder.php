<?php

namespace ProjectAnalyzer\Graph;

use ProjectAnalyzer\Analysis\Context;

class DependencyGraphBuilder
{
    /** @var array<string, array<int, string>> */
    private array $nodes = [];

    /** @var array<int, array{from: string, to: string, type: string}> */
    private array $edges = [];

    public function build(Context $context): array
    {
        $this->nodes = [];
        $this->edges = [];

        foreach ($context->classes as $class) {
            $fqn = $class['fqn'] ?? '';
            $this->addNode($fqn, $class['type'] ?? 'class');

            if (! empty($class['extends'])) {
                $this->addEdge($fqn, $class['extends'], 'extends');
            }

            foreach ($class['implements'] ?? [] as $interface) {
                $this->addEdge($fqn, $interface, 'implements');
            }

            foreach ($class['traits'] ?? [] as $trait) {
                $this->addEdge($fqn, $trait, 'uses');
            }
        }

        $controllerData = $context->getResult('controller') ?? [];
        foreach ($controllerData['controllers'] ?? [] as $controller) {
            $fqn = $controller['fqn'] ?? '';
            foreach ($controller['dependencies'] ?? [] as $dep) {
                if (is_string($dep)) {
                    $this->addEdge($fqn, $dep, 'depends_on');
                }
            }
        }

        return [
            'nodes' => $this->nodes,
            'edges' => $this->edges,
            'node_count' => count($this->nodes),
            'edge_count' => count($this->edges),
            'circular_dependencies' => $this->detectCircularDependencies(),
        ];
    }

    private function addNode(string $id, string $type): void
    {
        if (! isset($this->nodes[$id])) {
            $this->nodes[$id] = ['id' => $id, 'type' => $type];
        }
    }

    private function addEdge(string $from, string $to, string $type): void
    {
        $this->addNode($from, 'class');
        $this->addNode($to, 'class');
        $this->edges[] = ['from' => $from, 'to' => $to, 'type' => $type];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function detectCircularDependencies(): array
    {
        $cycles = [];
        $adjacency = [];

        foreach ($this->edges as $edge) {
            if ($edge['type'] === 'depends_on' || $edge['type'] === 'extends') {
                $adjacency[$edge['from']][] = $edge['to'];
            }
        }

        foreach (array_keys($adjacency) as $node) {
            $visited = [];
            $path = [];
            $this->dfsCycle($node, $adjacency, $visited, $path, $cycles);
        }

        return $cycles;
    }

    /**
     * @param  array<string, array<int, string>>  $adjacency
     * @param  array<string, bool>  $visited
     * @param  array<int, string>  $path
     * @param  array<int, array<int, string>>  $cycles
     */
    private function dfsCycle(string $node, array $adjacency, array &$visited, array &$path, array &$cycles): void
    {
        if (in_array($node, $path, true)) {
            $cycleStart = array_search($node, $path, true);
            $cycles[] = array_slice($path, $cycleStart);

            return;
        }

        if (isset($visited[$node])) {
            return;
        }

        $visited[$node] = true;
        $path[] = $node;

        foreach ($adjacency[$node] ?? [] as $neighbor) {
            $this->dfsCycle($neighbor, $adjacency, $visited, $path, $cycles);
        }

        array_pop($path);
    }
}
