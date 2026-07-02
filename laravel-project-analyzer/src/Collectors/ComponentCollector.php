<?php

namespace ProjectAnalyzer\Collectors;

use ProjectAnalyzer\Contracts\CollectorInterface;

class ComponentCollector implements CollectorInterface
{
    /**
     * @param  array<int, array<string, mixed>>  $classes
     */
    public function __construct(
        private readonly array $classes = [],
    ) {}

    public function collect(): array
    {
        $components = [
            'models' => [],
            'controllers' => [],
            'services' => [],
            'repositories' => [],
            'jobs' => [],
            'events' => [],
            'listeners' => [],
            'middleware' => [],
            'policies' => [],
            'requests' => [],
            'resources' => [],
            'commands' => [],
            'providers' => [],
            'observers' => [],
            'notifications' => [],
            'mailables' => [],
            'enums' => [],
            'traits' => [],
            'interfaces' => [],
            'tests' => [],
            'other' => [],
        ];

        foreach ($this->classes as $class) {
            $type = $this->detectComponentType($class);
            $components[$type][] = $class;
        }

        return $components;
    }

    public function getName(): string
    {
        return 'component';
    }

    /**
     * @param  array<string, mixed>  $class
     */
    private function detectComponentType(array $class): string
    {
        $fqn = $class['fqn'] ?? '';
        $file = $class['file'] ?? '';
        $type = $class['type'] ?? 'class';

        if ($type === 'trait') {
            return 'traits';
        }

        if ($type === 'interface') {
            return 'interfaces';
        }

        if ($type === 'enum') {
            return 'enums';
        }

        if (str_contains($file, 'tests/') || str_contains($file, 'Tests/')) {
            return 'tests';
        }

        if (str_ends_with($fqn, 'Controller') || str_contains($file, '/Controllers/')) {
            return 'controllers';
        }

        if ($this->extendsClass($class, 'Model') || str_contains($file, '/Models/')) {
            return 'models';
        }

        if (str_contains($file, '/Services/') || str_ends_with($fqn, 'Service')) {
            return 'services';
        }

        if (str_contains($file, '/Repositories/') || str_ends_with($fqn, 'Repository')) {
            return 'repositories';
        }

        if (str_contains($file, '/Jobs/') || str_ends_with($fqn, 'Job')) {
            return 'jobs';
        }

        if (str_contains($file, '/Events/')) {
            return 'events';
        }

        if (str_contains($file, '/Listeners/')) {
            return 'listeners';
        }

        if (str_contains($file, '/Http/Middleware/') || str_ends_with($fqn, 'Middleware')) {
            return 'middleware';
        }

        if (str_contains($file, '/Policies/') || str_ends_with($fqn, 'Policy')) {
            return 'policies';
        }

        if (str_contains($file, '/Http/Requests/') || str_ends_with($fqn, 'Request')) {
            return 'requests';
        }

        if (str_contains($file, '/Http/Resources/') || str_ends_with($fqn, 'Resource')) {
            return 'resources';
        }

        if (str_contains($file, '/Console/Commands/') || str_ends_with($fqn, 'Command')) {
            return 'commands';
        }

        if (str_contains($file, '/Providers/') || str_ends_with($fqn, 'ServiceProvider')) {
            return 'providers';
        }

        if (str_contains($file, '/Observers/') || str_ends_with($fqn, 'Observer')) {
            return 'observers';
        }

        if (str_contains($file, '/Notifications/')) {
            return 'notifications';
        }

        if (str_contains($file, '/Mail/')) {
            return 'mailables';
        }

        return 'other';
    }

    /**
     * @param  array<string, mixed>  $class
     */
    private function extendsClass(array $class, string $parent): bool
    {
        $extends = $class['extends'] ?? '';

        return str_contains($extends, $parent);
    }
}
