<?php

namespace ProjectAnalyzer\Analyzers;

use ProjectAnalyzer\Analysis\Context;

class ModelAnalyzer extends AbstractAnalyzer
{
    public function getName(): string
    {
        return 'model';
    }

    public function analyze(Context $context): array
    {
        $models = $this->filterClasses(
            $context->classes,
            fn ($c) => $this->classExtends($c, 'Model') || $this->classInPath($c, '/Models/')
        );

        $analyzed = [];

        foreach ($models as $model) {
            $analyzed[] = [
                'fqn' => $model['fqn'],
                'file' => $model['file'],
                'methods' => $model['methods'] ?? [],
                'traits' => $model['traits'] ?? [],
                'extends' => $model['extends'] ?? null,
                'relationships' => $this->detectRelationships($model),
                'table' => $this->guessTableName($model['name'] ?? ''),
            ];
        }

        return [
            'total' => count($analyzed),
            'models' => $analyzed,
        ];
    }

    /**
     * @param  array<string, mixed>  $model
     * @return array<int, string>
     */
    private function detectRelationships(array $model): array
    {
        $relationships = [];
        $relationshipMethods = ['hasMany', 'hasOne', 'belongsTo', 'belongsToMany', 'morphTo', 'morphMany', 'morphOne', 'hasManyThrough'];

        foreach ($model['methods'] ?? [] as $method) {
            $name = $method['name'] ?? '';
            if (in_array($name, $relationshipMethods, true)) {
                $relationships[] = $name;
            }
        }

        return $relationships;
    }

    private function guessTableName(string $className): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)).'s';
    }
}
