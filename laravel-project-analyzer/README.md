# Laravel Project Analyzer

A comprehensive Laravel package that performs static analysis of any Laravel application, generating detailed documentation, dependency graphs, test coverage reports, and actionable intelligence about project architecture and health.

## Features

- **Zero-Impact Analysis** — Complete static analysis without modifying application code
- **Comprehensive Discovery** — Models, controllers, routes, migrations, services, jobs, events, and more
- **Dependency Graphs** — Interactive Mermaid.js visualizations with circular dependency detection
- **Code Maps** — Component breakdowns, namespace concentration, and dependency hotspot visualizations
- **Health Scores** — Architecture, testability, security, and maintainability ratings
- **Test Intelligence** — Coverage analysis, missing test suggestions, and generated test stubs
- **Security Audit** — Detects dangerous functions, SQL injection risks, and superglobal usage
- **Insights** — Security, enhancement, and runtime cost recommendations
- **Auto Fix** — Applies supported fixes for test gaps, superglobal access, and service contracts
- **Validation** — Checks environment readiness, writable paths, and supported generation options
- **Documentation Generation** — Export to JSON, Markdown, and HTML
- **Interactive Dashboard** — Vue.js 3 + Inertia.js + Tailwind CSS SPA
- **Plugin Architecture** — Register custom analyzers and reporters

## Requirements

- PHP 8.2, 8.3, or 8.4
- Laravel 11.x, 12.x, or 13.x

## Installation

```Add to composer.json
"repositories": [
        {
            "type": "path",
            "url": "boa-intelligence/laravel-project-analyzer",
            "options": {
                "symlink": true
            }
        }
    ],
```

```bash
composer require boa-intelligence/laravel-project-analyzer
 composer require boa-intelligence/laravel-project-analyzer:dev-main
```

Publish configuration:

```bash
php artisan vendor:publish --tag=project-analyzer-config
```

Publish dashboard assets (automatic on first visit, or run manually):

```bash
php artisan vendor:publish --tag=project-analyzer-public
```

If `/analyzer` shows a blank page, force-republish the pre-built assets:

```bash
php artisan vendor:publish --tag=project-analyzer-public --force
php artisan optimize:clear
```

Then hard-refresh the browser. Open DevTools → Network and confirm `app-*.js` loads from `/vendor/project-analyzer/build/assets/` with status 200.

The dashboard works out of the box — no need to add package assets to your app's Vite config.

Optional — publish source assets for customization:

```bash
php artisan vendor:publish --tag=project-analyzer-assets
cd resources/vendor/project-analyzer && npm install && npm run build
php artisan vendor:publish --tag=project-analyzer-public --force
```

## Quick Start

Run a full project analysis:

```bash
php artisan project:analyze
```

Open the interactive dashboard:

```bash
php artisan serve
# Visit /analyzer
```

Export results:

```bash
php artisan project:analyze:export --format=json
php artisan project:analyze:report --format=html
```

## Artisan Commands

| Command                                        | Description               |
| ---------------------------------------------- | ------------------------- |
| `project:analyze`                              | Full project analysis     |
| `project:analyze --quick`                      | Use cached results        |
| `project:analyze --path=app/Services`          | Analyze specific path     |
| `project:analyze --analyzers=model,controller` | Run specific analyzers    |
| `project:analyze:export --format=json`         | Export analysis results   |
| `project:analyze:dashboard`                    | Show dashboard URL        |
| `project:analyze:docs`                         | Generate documentation    |
| `project:analyze:clear`                        | Clear analysis cache      |
| `project:analyze:tests --suggest`              | Test coverage suggestions |
| `project:analyze:tests --generate`             | Generate missing test stubs |
| `project:analyze:fix --apply`                  | Apply supported auto-fixes |
| `project:analyze:validate`                     | Validate environment and analyzer readiness |
| `project:analyze:database`                     | Database schema analysis  |
| `project:analyze:security`                     | Security audit            |
| `project:analyze:watch`                        | Watch for file changes    |
| `project:analyze:report --format=html`         | Generate HTML report      |

## Configuration

```php
// config/project-analyzer.php

return [
    'analysis' => [
        'paths' => ['app', 'database', 'routes', 'config', 'tests'],
        'exclude' => ['vendor', 'storage', 'bootstrap/cache'],
        'depth' => 'full',
    ],
    'analyzers' => [
        'class' => true,
        'model' => true,
        'controller' => true,
        'route' => true,
        'database' => true,
        'service' => true,
        'test' => true,
        'security' => true,
    ],
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
    ],
    'dashboard' => [
        'enabled' => true,
        'route_prefix' => 'analyzer',
        'middleware' => ['web'],
    ],
];
```

## Plugin Architecture

Register custom analyzers:

```php
use ProjectAnalyzer\Facades\Analyzer;
use ProjectAnalyzer\Contracts\AnalyzerInterface;
use ProjectAnalyzer\Analysis\Context;

class CustomAnalyzer implements AnalyzerInterface
{
    public function analyze(Context $context): array
    {
        return ['custom_metric' => 42];
    }

    public function getName(): string { return 'custom'; }
    public function getPriority(): int { return 100; }
    public function isEnabled(): bool { return true; }
}

// In a service provider:
Analyzer::register(new CustomAnalyzer());
```

## Dashboard

The dashboard provides twelve views:

1. **Overview** — Health scores and quick statistics
2. **Components** — Searchable component explorer
3. **Graphs** — Dependency and ER diagrams (Mermaid.js)
4. **Code Maps** — Component distributions, namespace concentration, and class density views
5. **Tests** — Coverage charts (Chart.js) and missing tests
6. **Test Generator** — Preview and generate Pest/PHPUnit stubs for missing coverage
7. **Auto Fix** — Apply supported low-risk fixes
8. **Metrics** — Complexity analysis and largest classes
9. **Insights** — Security, enhancement, and runtime cost intelligence
10. **Validation** — Environment and configuration checks
11. **Recommendations** — Prioritized improvement suggestions
12. **Settings** — Configuration and export options

Features dark/light theme toggle and responsive Tailwind CSS design.

## API Usage

```php
use ProjectAnalyzer\Engine\AnalysisEngine;

$engine = app(AnalysisEngine::class);
$result = $engine->analyze();

$healthScore = $result->metrics['overall'];
$recommendations = $result->recommendations;
$models = $result->data['model']['models'] ?? [];
```

## Testing

```bash
composer test
composer test:coverage
composer test-coverage
```

Coverage requires a local coverage driver such as **Xdebug** or **PCOV**. If neither is installed, Pest/PHPUnit cannot produce a coverage report.

For local coverage with Xdebug:

```bash
XDEBUG_MODE=coverage composer test:coverage
```

The package also includes CI coverage verification in `.github/workflows/coverage.yml`, which runs:

```bash
XDEBUG_MODE=coverage ./vendor/bin/pest --coverage --min=85
```

## Development

```bash
composer install
npm install
npm run dev
composer test
composer lint
```

## License

MIT License. See [LICENSE](LICENSE) for details.
