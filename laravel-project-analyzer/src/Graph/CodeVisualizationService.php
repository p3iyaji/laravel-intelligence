<?php

namespace ProjectAnalyzer\Graph;

use ProjectAnalyzer\Analysis\Context;

class CodeVisualizationService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Context $context): array
    {
        $components = $context->getResult('components') ?? [];
        $graph = $context->getResult('graph') ?? [];
        $routes = $context->getResult('route') ?? [];

        return [
            'component_breakdown' => $this->componentBreakdown($components),
            'namespace_breakdown' => $this->namespaceBreakdown($context->classes),
            'dependency_hotspots' => $this->dependencyHotspots($graph),
            'class_size_heatmap' => $this->classSizeHeatmap($context->classes),
            'route_activity' => $this->routeActivity($routes),
        ];
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $components
     * @return array<int, array<string, int|string>>
     */
    private function componentBreakdown(array $components): array
    {
        $breakdown = [];

        foreach ($components as $type => $items) {
            if (is_array($items) && count($items) > 0) {
                $breakdown[] = [
                    'label' => $type,
                    'value' => count($items),
                ];
            }
        }

        usort($breakdown, fn ($a, $b) => $b['value'] <=> $a['value']);

        return $breakdown;
    }

    /**
     * @param  array<int, array<string, mixed>>  $classes
     * @return array<int, array<string, int|string>>
     */
    private function namespaceBreakdown(array $classes): array
    {
        $counts = [];

        foreach ($classes as $class) {
            $namespace = (string) ($class['namespace'] ?? 'global');
            $counts[$namespace] = ($counts[$namespace] ?? 0) + 1;
        }

        $breakdown = [];
        foreach ($counts as $namespace => $count) {
            $breakdown[] = [
                'label' => $namespace,
                'value' => $count,
            ];
        }

        usort($breakdown, fn ($a, $b) => $b['value'] <=> $a['value']);

        return array_slice($breakdown, 0, 10);
    }

    /**
     * @param  array<string, mixed>  $graph
     * @return array<int, array<string, int|string>>
     */
    private function dependencyHotspots(array $graph): array
    {
        $scores = [];
        $types = [];

        foreach ($graph['nodes'] ?? [] as $node) {
            $id = (string) ($node['id'] ?? '');
            $scores[$id] = [
                'incoming' => 0,
                'outgoing' => 0,
            ];
            $types[$id] = (string) ($node['type'] ?? 'class');
        }

        foreach ($graph['edges'] ?? [] as $edge) {
            $from = (string) ($edge['from'] ?? '');
            $to = (string) ($edge['to'] ?? '');

            if (isset($scores[$from])) {
                $scores[$from]['outgoing']++;
            }

            if (isset($scores[$to])) {
                $scores[$to]['incoming']++;
            }
        }

        $hotspots = [];
        foreach ($scores as $id => $score) {
            $hotspots[] = [
                'label' => $id,
                'type' => $types[$id] ?? 'class',
                'incoming' => $score['incoming'],
                'outgoing' => $score['outgoing'],
                'total' => $score['incoming'] + $score['outgoing'],
            ];
        }

        usort($hotspots, fn ($a, $b) => $b['total'] <=> $a['total']);

        return array_slice($hotspots, 0, 10);
    }

    /**
     * @param  array<int, array<string, mixed>>  $classes
     * @return array<int, array<string, int|string>>
     */
    private function classSizeHeatmap(array $classes): array
    {
        $items = [];

        foreach ($classes as $class) {
            $items[] = [
                'label' => (string) ($class['fqn'] ?? ''),
                'type' => (string) ($class['type'] ?? 'class'),
                'methods' => (int) ($class['method_count'] ?? 0),
                'file' => (string) ($class['file'] ?? ''),
            ];
        }

        usort($items, fn ($a, $b) => $b['methods'] <=> $a['methods']);

        return array_slice($items, 0, 12);
    }

    /**
     * @param  array<string, mixed>  $routes
     * @return array<int, array<string, int|string>>
     */
    private function routeActivity(array $routes): array
    {
        $counts = [];

        foreach ($routes['routes'] ?? [] as $route) {
            foreach ($route['methods'] ?? [] as $method) {
                $counts[$method] = ($counts[$method] ?? 0) + 1;
            }
        }

        $activity = [];
        foreach ($counts as $method => $count) {
            $activity[] = [
                'label' => $method,
                'value' => $count,
            ];
        }

        usort($activity, fn ($a, $b) => $b['value'] <=> $a['value']);

        return $activity;
    }
}
