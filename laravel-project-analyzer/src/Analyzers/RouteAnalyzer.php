<?php

namespace ProjectAnalyzer\Analyzers;

use Illuminate\Support\Facades\Route;
use ProjectAnalyzer\Analysis\Context;

class RouteAnalyzer extends AbstractAnalyzer
{
    public function getName(): string
    {
        return 'route';
    }

    public function analyze(Context $context): array
    {
        $routes = [];

        try {
            foreach (Route::getRoutes() as $route) {
                $action = $route->getAction();
                $controller = $action['controller'] ?? null;

                $routes[] = [
                    'uri' => $route->uri(),
                    'methods' => $route->methods(),
                    'name' => $route->getName(),
                    'controller' => $controller,
                    'middleware' => $route->middleware(),
                    'domain' => $route->getDomain(),
                ];
            }
        } catch (\Throwable) {
            $routes = $this->analyzeRouteFiles($context);
        }

        $byMethod = [];
        foreach ($routes as $route) {
            foreach ($route['methods'] ?? ['GET'] as $method) {
                $byMethod[$method] = ($byMethod[$method] ?? 0) + 1;
            }
        }

        return [
            'total' => count($routes),
            'by_method' => $byMethod,
            'routes' => $routes,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function analyzeRouteFiles(Context $context): array
    {
        $routes = [];

        foreach ($context->files as $file) {
            $path = $file['path'] ?? '';
            if (! str_contains($path, 'routes/')) {
                continue;
            }

            $content = file_get_contents($file['absolute_path'] ?? '');
            if ($content === false) {
                continue;
            }

            preg_match_all('/Route::(get|post|put|patch|delete|any|match)\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $routes[] = [
                    'uri' => $match[2],
                    'methods' => [strtoupper($match[1])],
                    'name' => null,
                    'controller' => null,
                    'middleware' => [],
                    'source_file' => $path,
                ];
            }
        }

        return $routes;
    }
}
