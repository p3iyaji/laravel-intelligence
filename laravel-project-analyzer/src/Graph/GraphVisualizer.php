<?php

namespace ProjectAnalyzer\Graph;

class GraphVisualizer
{
    /**
     * @param  array<string, mixed>  $graph
     */
    public function toMermaid(array $graph): string
    {
        $lines = ['graph TD'];

        foreach ($graph['nodes'] ?? [] as $node) {
            $id = $this->sanitizeId($node['id'] ?? '');
            $label = str_replace(['"', "'"], '', $node['id'] ?? '');
            $type = $node['type'] ?? 'class';
            $lines[] = "    {$id}[\"{$label}<br/><small>{$type}</small>\"]";
        }

        foreach ($graph['edges'] ?? [] as $edge) {
            $from = $this->sanitizeId($edge['from'] ?? '');
            $to = $this->sanitizeId($edge['to'] ?? '');
            $type = $edge['type'] ?? '';
            $lines[] = "    {$from} -->|{$type}| {$to}";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $relationships
     */
    public function toErDiagram(array $relationships): string
    {
        $lines = ['erDiagram'];

        foreach ($relationships['model_to_table'] ?? [] as $mapping) {
            $table = $mapping['table'] ?? 'unknown';
            $model = class_basename($mapping['model'] ?? 'Unknown');
            $lines[] = "    {$table} {";
            $lines[] = '        int id PK';
            $lines[] = '    }';
            $lines[] = "    {$model} ||--|| {$table} : maps_to";
        }

        foreach ($relationships['foreign_keys'] ?? [] as $fk) {
            $column = $fk['column'] ?? 'unknown';
            $lines[] = "    table_{$column} }o--|| referenced_table : has_fk";
        }

        return implode("\n", $lines);
    }

    private function sanitizeId(string $id): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $id);
    }

    /**
     * @param  array<string, mixed>  $class
     */
    public function toClassDiagram(array $class): string
    {
        $className = $this->sanitizeMermaidLabel(class_basename($class['fqn'] ?? 'Unknown'));
        $lines = ['classDiagram'];

        $lines[] = "    class {$className} {";
        foreach ($class['methods'] ?? [] as $method) {
            $visibility = match ($method['visibility'] ?? 'public') {
                'private' => '-',
                'protected' => '#',
                default => '+',
            };
            $paramCount = $method['parameters'] ?? 0;
            $returnType = $method['return_type'] ?? '';
            $returnSuffix = $returnType !== '' && $returnType !== 'mixed' ? " {$returnType}" : '';
            $staticPrefix = ($method['is_static'] ?? false) ? '{static} ' : '';
            $lines[] = "        {$visibility}{$staticPrefix}{$method['name']}({$paramCount}){$returnSuffix}";
        }
        $lines[] = '    }';

        if (! empty($class['extends'])) {
            $parent = $this->sanitizeMermaidLabel(class_basename($class['extends']));
            $lines[] = "    {$parent} <|-- {$className}";
        }

        foreach ($class['implements'] ?? [] as $interface) {
            $iface = $this->sanitizeMermaidLabel(class_basename($interface));
            $lines[] = "    {$iface} <|.. {$className}";
        }

        foreach ($class['traits'] ?? [] as $trait) {
            $traitName = $this->sanitizeMermaidLabel(class_basename($trait));
            $lines[] = "    {$traitName} <|.. {$className} : uses";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $graph
     */
    public function toSubgraph(array $graph, string $fqn): string
    {
        $related = [$fqn];
        $edges = $graph['edges'] ?? [];

        foreach ($edges as $edge) {
            if (($edge['from'] ?? '') === $fqn) {
                $related[] = $edge['to'] ?? '';
            }
            if (($edge['to'] ?? '') === $fqn) {
                $related[] = $edge['from'] ?? '';
            }
        }

        $related = array_values(array_unique(array_filter($related)));
        $relatedSet = array_flip($related);

        $subgraph = [
            'nodes' => array_values(array_filter(
                $graph['nodes'] ?? [],
                fn ($node) => isset($relatedSet[$node['id'] ?? ''])
            )),
            'edges' => array_values(array_filter(
                $edges,
                fn ($edge) => isset($relatedSet[$edge['from'] ?? '']) && isset($relatedSet[$edge['to'] ?? ''])
            )),
        ];

        return $this->toMermaid($subgraph);
    }

    private function sanitizeMermaidLabel(string $label): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $label) ?: 'Unknown';
    }
}
