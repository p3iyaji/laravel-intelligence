<?php

namespace ProjectAnalyzer\Graph;

use ProjectAnalyzer\Analysis\Context;

class RelationshipMapper
{
    public function map(Context $context): array
    {
        $modelData = $context->getResult('model') ?? [];
        $databaseData = $context->getResult('database') ?? [];
        $routeData = $context->getResult('route') ?? [];

        $relationships = [
            'model_to_table' => [],
            'model_relationships' => [],
            'route_to_controller' => [],
            'foreign_keys' => [],
        ];

        foreach ($modelData['models'] ?? [] as $model) {
            $relationships['model_to_table'][] = [
                'model' => $model['fqn'],
                'table' => $model['table'] ?? null,
            ];

            if (! empty($model['relationships'])) {
                $relationships['model_relationships'][] = [
                    'model' => $model['fqn'],
                    'relationships' => $model['relationships'],
                ];
            }
        }

        foreach ($databaseData['migrations'] ?? [] as $migration) {
            foreach ($migration['foreign_keys'] ?? [] as $fk) {
                $relationships['foreign_keys'][] = [
                    'column' => $fk,
                    'migration' => $migration['file'],
                ];
            }
        }

        foreach ($routeData['routes'] ?? [] as $route) {
            if (! empty($route['controller'])) {
                $relationships['route_to_controller'][] = [
                    'uri' => $route['uri'],
                    'controller' => $route['controller'],
                    'methods' => $route['methods'],
                ];
            }
        }

        return $relationships;
    }
}
