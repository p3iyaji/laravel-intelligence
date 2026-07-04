<?php

namespace ProjectAnalyzer\Engine;

use ProjectAnalyzer\Analysis\Context;
use ProjectAnalyzer\Analysis\Result;
use ProjectAnalyzer\Collectors\ClassCollector;
use ProjectAnalyzer\Collectors\ComponentCollector;
use ProjectAnalyzer\Collectors\FileCollector;
use ProjectAnalyzer\Contracts\AnalyzerInterface;
use ProjectAnalyzer\Metrics\HealthScoreCalculator;
use ProjectAnalyzer\Recommendations\RecommendationEngine;
use Illuminate\Support\Facades\Cache;

class AnalysisEngine
{
    /** @var array<int, AnalyzerInterface> */
    private array $analyzers = [];

    public function __construct(
        private readonly HealthScoreCalculator $healthCalculator,
        private readonly RecommendationEngine $recommendationEngine,
    ) {}

    public function registerAnalyzer(AnalyzerInterface $analyzer): void
    {
        $this->analyzers[] = $analyzer;
        usort($this->analyzers, fn ($a, $b) => $a->getPriority() <=> $b->getPriority());
    }

    /**
     * @param  array<int, AnalyzerInterface>  $analyzers
     */
    public function registerAnalyzers(array $analyzers): void
    {
        foreach ($analyzers as $analyzer) {
            $this->registerAnalyzer($analyzer);
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function analyze(array $options = []): Result
    {
        $config = config('project-analyzer', []);
        $basePath = $options['base_path'] ?? base_path();
        $paths = $options['paths'] ?? $config['analysis']['paths'] ?? ['app'];
        $exclude = $options['exclude'] ?? $config['analysis']['exclude'] ?? [];
        $cacheKey = 'project-analyzer:'.md5($basePath.serialize($paths));

        if (($options['quick'] ?? false) && ($config['cache']['enabled'] ?? true)) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return Result::fromContext(
                    new Context($config, $basePath, $paths, $exclude, results: $cached['data']),
                    $cached['metrics'] ?? [],
                    $cached['recommendations'] ?? [],
                );
            }
        }

        $fileCollector = new FileCollector($basePath, $paths, $exclude);
        $files = $fileCollector->collect();

        $classCollector = new ClassCollector($files);
        $classes = $classCollector->collect();

        $componentCollector = new ComponentCollector($classes);
        $components = $componentCollector->collect();

        $context = new Context(
            config: $config,
            basePath: $basePath,
            paths: $paths,
            exclude: $exclude,
            classes: $classes,
            files: $files,
        );

        $context->addResult('files', ['total' => count($files), 'files' => $files]);
        $context->addResult('components', $components);

        $enabledAnalyzers = $options['analyzers'] ?? null;

        foreach ($this->analyzers as $analyzer) {
            if (! $analyzer->isEnabled()) {
                continue;
            }

            $name = strtolower(str_replace(' Analyzer', '', $analyzer->getName()));
            $name = str_replace(' ', '_', $name);

            if ($enabledAnalyzers !== null) {
                $allowed = array_map('trim', explode(',', $enabledAnalyzers));
                if (! in_array($name, $allowed, true)) {
                    continue;
                }
            }

            $context->addResult($name, $analyzer->analyze($context));
        }

        $graphBuilder = app(\ProjectAnalyzer\Graph\DependencyGraphBuilder::class);
        $relationshipMapper = app(\ProjectAnalyzer\Graph\RelationshipMapper::class);
        $visualizationService = app(\ProjectAnalyzer\Graph\CodeVisualizationService::class);
        $validationService = app(\ProjectAnalyzer\Validation\ValidationService::class);
        $context->addResult('graph', $graphBuilder->build($context));
        $context->addResult('relationships', $relationshipMapper->map($context));
        $context->addResult('visualizations', $visualizationService->build($context));
        $context->addResult('validation', $validationService->validateEnvironment($basePath, $config));

        $metrics = $this->healthCalculator->calculate($context);
        $recommendations = $this->recommendationEngine->generate($context);

        $result = Result::fromContext($context, $metrics, $recommendations);

        if ($config['cache']['enabled'] ?? true) {
            Cache::put($cacheKey, $result->toArray(), $config['cache']['ttl'] ?? 3600);
        }

        return $result;
    }

    public function clearCache(): void
    {
        Cache::flush();
    }

    /**
     * @return array<int, AnalyzerInterface>
     */
    public function getAnalyzers(): array
    {
        return $this->analyzers;
    }
}
