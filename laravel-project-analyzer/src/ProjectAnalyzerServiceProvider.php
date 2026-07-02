<?php

namespace ProjectAnalyzer;

use Illuminate\Support\ServiceProvider;
use ProjectAnalyzer\Analyzers\ClassAnalyzer;
use ProjectAnalyzer\Analyzers\ControllerAnalyzer;
use ProjectAnalyzer\Analyzers\DatabaseAnalyzer;
use ProjectAnalyzer\Analyzers\ModelAnalyzer;
use ProjectAnalyzer\Analyzers\RouteAnalyzer;
use ProjectAnalyzer\Analyzers\SecurityAnalyzer;
use ProjectAnalyzer\Analyzers\ServiceAnalyzer;
use ProjectAnalyzer\Analyzers\TestAnalyzer;
use ProjectAnalyzer\Commands\AnalyzeCommand;
use ProjectAnalyzer\Commands\ClearCacheCommand;
use ProjectAnalyzer\Commands\DatabaseAnalysisCommand;
use ProjectAnalyzer\Commands\ExportReportCommand;
use ProjectAnalyzer\Commands\GenerateDocsCommand;
use ProjectAnalyzer\Commands\ReportCommand;
use ProjectAnalyzer\Commands\SecurityAuditCommand;
use ProjectAnalyzer\Commands\ServeDashboardCommand;
use ProjectAnalyzer\Commands\TestSuggestionsCommand;
use ProjectAnalyzer\Commands\WatchCommand;
use ProjectAnalyzer\Engine\AnalysisEngine;
use ProjectAnalyzer\Generators\HtmlGenerator;
use ProjectAnalyzer\Generators\JsonGenerator;
use ProjectAnalyzer\Generators\MarkdownGenerator;
use ProjectAnalyzer\Generators\ReportExporter;
use ProjectAnalyzer\Graph\DependencyGraphBuilder;
use ProjectAnalyzer\Graph\GraphVisualizer;
use ProjectAnalyzer\Graph\RelationshipMapper;
use ProjectAnalyzer\Metrics\ComplexityCalculator;
use ProjectAnalyzer\Metrics\CoverageCalculator;
use ProjectAnalyzer\Metrics\HealthScoreCalculator;
use ProjectAnalyzer\Plugins\PluginManager;
use ProjectAnalyzer\Recommendations\RecommendationEngine;

class ProjectAnalyzerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/project-analyzer.php', 'project-analyzer');

        $this->app->singleton(ComplexityCalculator::class);
        $this->app->singleton(CoverageCalculator::class);
        $this->app->singleton(DependencyGraphBuilder::class);
        $this->app->singleton(RelationshipMapper::class);
        $this->app->singleton(GraphVisualizer::class);
        $this->app->singleton(RecommendationEngine::class);

        $this->app->singleton(HealthScoreCalculator::class, function ($app) {
            return new HealthScoreCalculator(
                $app->make(ComplexityCalculator::class),
                $app->make(CoverageCalculator::class),
                $app->make(DependencyGraphBuilder::class),
            );
        });

        $this->app->singleton(AnalysisEngine::class, function ($app) {
            $engine = new AnalysisEngine(
                $app->make(HealthScoreCalculator::class),
                $app->make(RecommendationEngine::class),
            );

            $analyzers = [
                new ClassAnalyzer,
                new ModelAnalyzer,
                new ControllerAnalyzer,
                new RouteAnalyzer,
                new DatabaseAnalyzer,
                new ServiceAnalyzer,
                new TestAnalyzer,
                new SecurityAnalyzer,
            ];

            $config = config('project-analyzer.analyzers', []);
            foreach ($analyzers as $analyzer) {
                $key = $analyzer->getName();
                if ($config[$key] ?? true) {
                    $engine->registerAnalyzer($analyzer);
                }
            }

            return $engine;
        });

        $this->app->singleton(ReportExporter::class, function ($app) {
            $exporter = new ReportExporter(
                $app->make(DependencyGraphBuilder::class),
                $app->make(RelationshipMapper::class),
            );

            $exporter->registerReporter(new JsonGenerator);
            $exporter->registerReporter(new MarkdownGenerator($app->make(GraphVisualizer::class)));
            $exporter->registerReporter(new HtmlGenerator($app->make(GraphVisualizer::class)));

            return $exporter;
        });

        $this->app->singleton(PluginManager::class, function ($app) {
            return new PluginManager(
                $app->make(AnalysisEngine::class),
                $app->make(ReportExporter::class),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AnalyzeCommand::class,
                ExportReportCommand::class,
                ServeDashboardCommand::class,
                GenerateDocsCommand::class,
                ClearCacheCommand::class,
                TestSuggestionsCommand::class,
                DatabaseAnalysisCommand::class,
                SecurityAuditCommand::class,
                ReportCommand::class,
                WatchCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/project-analyzer.php' => config_path('project-analyzer.php'),
            ], 'project-analyzer-config');
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'project-analyzer');

        if (config('project-analyzer.dashboard.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/dashboard.php');
        }

        $this->publishes([
            __DIR__.'/../resources/assets' => resource_path('vendor/project-analyzer'),
        ], 'project-analyzer-assets');
    }
}
