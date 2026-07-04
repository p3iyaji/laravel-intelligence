<?php

use ProjectAnalyzer\Analysis\Context;
use ProjectAnalyzer\Contracts\AnalyzerInterface;
use ProjectAnalyzer\Engine\AnalysisEngine;
use ProjectAnalyzer\Plugins\PluginManager;
use ProjectAnalyzer\Tests\TestCase;

uses(TestCase::class);

describe('AnalysisEngine', function () {
    it('runs full analysis on fixtures', function () {
        $fixturePath = __DIR__.'/../Fixtures';

        $this->app['config']->set('project-analyzer.cache.enabled', false);
        $this->app['config']->set('project-analyzer.analysis.paths', ['app', 'database', 'routes', 'tests']);

        $engine = app(AnalysisEngine::class);
        $result = $engine->analyze(['base_path' => $fixturePath]);

        expect($result->data)->not->toBeEmpty();
        expect($result->metrics)->toHaveKey('overall');
        expect($result->metrics['statistics']['total_classes'])->toBeGreaterThan(0);
        expect($result->data)->toHaveKey('visualizations');
        expect($result->data)->toHaveKey('cost');
        expect($result->data)->toHaveKey('validation');
        expect(collect($result->recommendations)->pluck('category'))->toContain('cost');
    });

    it('filters analyzers', function () {
        $fixturePath = __DIR__.'/../Fixtures';
        $this->app['config']->set('project-analyzer.cache.enabled', false);

        $engine = app(AnalysisEngine::class);
        $result = $engine->analyze([
            'base_path' => $fixturePath,
            'analyzers' => 'model',
        ]);

        expect($result->data)->toHaveKey('model');
    });

    it('clears cache', function () {
        $engine = app(AnalysisEngine::class);
        $engine->clearCache();

        expect(true)->toBeTrue();
    });
});

describe('PluginManager', function () {
    it('registers custom analyzers', function () {
        $customAnalyzer = new class implements AnalyzerInterface
        {
            public function analyze(Context $context): array
            {
                return ['custom_metric' => 42];
            }

            public function getName(): string
            {
                return 'custom';
            }

            public function getPriority(): int
            {
                return 100;
            }

            public function isEnabled(): bool
            {
                return true;
            }
        };

        $manager = app(PluginManager::class);
        $manager->register($customAnalyzer);

        expect($manager->getCustomAnalyzers())->toHaveCount(1);
    });
});

describe('Artisan Commands', function () {
    it('runs project:analyze command', function () {
        $this->app['config']->set('project-analyzer.cache.enabled', false);

        $this->artisan('project:analyze')
            ->assertExitCode(0);
    });

    it('runs project:analyze:clear command', function () {
        $this->artisan('project:analyze:clear')
            ->assertExitCode(0);
    });

    it('runs project:analyze:tests command', function () {
        $this->artisan('project:analyze:tests', ['--suggest' => true])
            ->assertExitCode(0);
    });

    it('generates test stubs from project:analyze:tests command', function () {
        $basePath = sys_get_temp_dir().'/project-analyzer-command-'.uniqid();
        mkdir($basePath.'/app/Services', 0755, true);
        mkdir($basePath.'/tests', 0755, true);
        file_put_contents($basePath.'/app/Services/BillingService.php', <<<'PHP'
<?php

namespace App\Services;

class BillingService
{
    public function charge(): bool
    {
        return true;
    }
}
PHP);

        $this->artisan('project:analyze:tests', [
            '--generate' => true,
            '--base-path' => $basePath,
            '--framework' => 'pest',
        ])->assertExitCode(0);

        expect(file_exists($basePath.'/tests/Unit/BillingServiceTest.php'))->toBeTrue();

        unlink($basePath.'/tests/Unit/BillingServiceTest.php');
        unlink($basePath.'/app/Services/BillingService.php');
        rmdir($basePath.'/tests/Unit');
        rmdir($basePath.'/tests');
        rmdir($basePath.'/app/Services');
        rmdir($basePath.'/app');
        rmdir($basePath);
    });

    it('runs project:analyze:database command', function () {
        $this->artisan('project:analyze:database')
            ->assertExitCode(0);
    });

    it('runs project:analyze:security command', function () {
        $this->artisan('project:analyze:security')
            ->assertExitCode(0);
    });

    it('runs project:analyze:dashboard command', function () {
        $this->artisan('project:analyze:dashboard')
            ->assertExitCode(0);
    });

    it('runs project:analyze:fix command', function () {
        $basePath = sys_get_temp_dir().'/project-analyzer-fix-command-'.uniqid();
        mkdir($basePath.'/app/Services', 0755, true);
        mkdir($basePath.'/app/Http/Controllers', 0755, true);
        mkdir($basePath.'/tests', 0755, true);

        file_put_contents($basePath.'/app/Services/BillingService.php', <<<'PHP'
<?php

namespace App\Services;

class BillingService
{
    public function charge(): bool
    {
        return true;
    }
}
PHP);

        file_put_contents($basePath.'/app/Http/Controllers/LegacyController.php', <<<'PHP'
<?php

namespace App\Http\Controllers;

class LegacyController
{
    public function index(): string
    {
        return $_GET['filter'];
    }
}
PHP);

        $this->artisan('project:analyze:fix', [
            '--apply' => true,
            '--base-path' => $basePath,
        ])->assertExitCode(0);

        expect(file_exists($basePath.'/tests/Unit/BillingServiceTest.php'))->toBeTrue();
        expect(file_exists($basePath.'/app/Services/BillingServiceInterface.php'))->toBeTrue();

        unlink($basePath.'/tests/Unit/BillingServiceTest.php');
        if (file_exists($basePath.'/tests/Feature/LegacyControllerTest.php')) {
            unlink($basePath.'/tests/Feature/LegacyControllerTest.php');
            rmdir($basePath.'/tests/Feature');
        }
        unlink($basePath.'/app/Services/BillingServiceInterface.php');
        unlink($basePath.'/app/Services/BillingService.php');
        unlink($basePath.'/app/Http/Controllers/LegacyController.php');
        rmdir($basePath.'/tests/Unit');
        rmdir($basePath.'/tests');
        rmdir($basePath.'/app/Services');
        rmdir($basePath.'/app/Http/Controllers');
        rmdir($basePath.'/app/Http');
        rmdir($basePath.'/app');
        rmdir($basePath);
    });

    it('runs project:analyze:validate command', function () {
        $this->artisan('project:analyze:validate')
            ->assertExitCode(0);
    });
});

describe('Dashboard Routes', function () {
    it('loads dashboard index', function () {
        $response = $this->get('/analyzer');

        $response->assertStatus(200);
    });

    it('loads components page', function () {
        $response = $this->get('/analyzer/components');

        $response->assertStatus(200);
    });

    it('loads graphs page', function () {
        $response = $this->get('/analyzer/graphs');

        $response->assertStatus(200);
    });

    it('loads code visualization page', function () {
        $response = $this->get('/analyzer/code-visualization');

        $response->assertStatus(200);
    });

    it('loads tests page', function () {
        $response = $this->get('/analyzer/tests');

        $response->assertStatus(200);
    });

    it('loads test generation page', function () {
        $response = $this->get('/analyzer/test-generation');

        $response->assertStatus(200);
    });

    it('loads auto fix page', function () {
        $response = $this->get('/analyzer/auto-fix');

        $response->assertStatus(200);
    });

    it('loads metrics page', function () {
        $response = $this->get('/analyzer/metrics');

        $response->assertStatus(200);
    });

    it('loads insights page', function () {
        $response = $this->get('/analyzer/insights');

        $response->assertStatus(200);
    });

    it('loads validation page', function () {
        $response = $this->get('/analyzer/validation');

        $response->assertStatus(200);
    });

    it('loads recommendations page', function () {
        $response = $this->get('/analyzer/recommendations');

        $response->assertStatus(200);
    });

    it('loads settings page', function () {
        $response = $this->get('/analyzer/settings');

        $response->assertStatus(200);
    });

    it('generates tests from dashboard endpoint', function () {
        $response = $this->postJson('/analyzer/test-generation/generate', [
            'framework' => 'pest',
            'force' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['written', 'skipped', 'written_count', 'skipped_count']);

        foreach (($response->json('written') ?? []) as $path) {
            $absolutePath = base_path($path);
            if (file_exists($absolutePath)) {
                unlink($absolutePath);
            }
        }
    });

    it('applies auto fixes from dashboard endpoint', function () {
        $response = $this->postJson('/analyzer/auto-fix/apply', [
            'force' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['applied', 'skipped', 'applied_count', 'skipped_count']);
    });
});

describe('Report Export', function () {
    it('exports json report', function () {
        $fixturePath = __DIR__.'/../Fixtures';
        $this->app['config']->set('project-analyzer.cache.enabled', false);
        $this->app['config']->set('project-analyzer.export.location', sys_get_temp_dir().'/project-analysis-test');

        $engine = app(AnalysisEngine::class);
        $result = $engine->analyze(['base_path' => $fixturePath]);

        $exporter = app(\ProjectAnalyzer\Generators\ReportExporter::class);
        $path = $exporter->export($result, 'json');

        expect(file_exists($path))->toBeTrue();
        expect(json_decode(file_get_contents($path), true))->toBeArray();

        @unlink($path);
    });

    it('exports markdown report', function () {
        $fixturePath = __DIR__.'/../Fixtures';
        $this->app['config']->set('project-analyzer.export.location', sys_get_temp_dir().'/project-analysis-test');

        $engine = app(AnalysisEngine::class);
        $result = $engine->analyze(['base_path' => $fixturePath, 'quick' => false]);

        $exporter = app(\ProjectAnalyzer\Generators\ReportExporter::class);
        $path = $exporter->export($result, 'markdown');

        expect(file_exists($path))->toBeTrue();
        expect(file_get_contents($path))->toContain('# Project Analysis Report');

        @unlink($path);
    });

    it('exports html report', function () {
        $fixturePath = __DIR__.'/../Fixtures';
        $this->app['config']->set('project-analyzer.export.location', sys_get_temp_dir().'/project-analysis-test');

        $engine = app(AnalysisEngine::class);
        $result = $engine->analyze(['base_path' => $fixturePath, 'quick' => false]);

        $exporter = app(\ProjectAnalyzer\Generators\ReportExporter::class);
        $path = $exporter->export($result, 'html');

        expect(file_exists($path))->toBeTrue();
        expect(file_get_contents($path))->toContain('<!DOCTYPE html>');

        @unlink($path);
    });
});

describe('Parsing', function () {
    it('parses php files with ClassVisitor', function () {
        $path = __DIR__.'/../Fixtures/app/Models/User.php';
        $ast = \ProjectAnalyzer\Parsing\PhpParserFactory::parseFile($path);

        expect($ast)->not->toBeNull();
        expect($ast)->toBeArray();
    });

    it('returns null for invalid file', function () {
        $ast = \ProjectAnalyzer\Parsing\PhpParserFactory::parseFile('/nonexistent/file.php');
        expect($ast)->toBeNull();
    });
});

describe('Contracts', function () {
    it('abstract analyzer can be disabled', function () {
        $analyzer = new \ProjectAnalyzer\Analyzers\ClassAnalyzer;
        $analyzer->setEnabled(false);

        expect($analyzer->isEnabled())->toBeFalse();
    });
});
