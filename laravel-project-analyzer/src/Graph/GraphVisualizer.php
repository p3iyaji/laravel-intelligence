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
}
