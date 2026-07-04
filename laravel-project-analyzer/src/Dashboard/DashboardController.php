<?php

namespace ProjectAnalyzer\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use ProjectAnalyzer\Engine\AnalysisEngine;
use ProjectAnalyzer\Fixes\AutoFixService;
use ProjectAnalyzer\Graph\CodeVisualizationService;
use ProjectAnalyzer\Graph\DependencyGraphBuilder;
use ProjectAnalyzer\Graph\GraphVisualizer;
use ProjectAnalyzer\Graph\RelationshipMapper;
use ProjectAnalyzer\Testing\TestGenerationService;
use ProjectAnalyzer\Validation\ValidationService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AnalysisEngine $engine,
        private readonly AutoFixService $autoFixService,
        private readonly CodeVisualizationService $codeVisualizationService,
        private readonly DependencyGraphBuilder $graphBuilder,
        private readonly RelationshipMapper $relationshipMapper,
        private readonly GraphVisualizer $visualizer,
        private readonly TestGenerationService $testGenerationService,
        private readonly ValidationService $validationService,
    ) {}

    public function index(Request $request): Response
    {
        $result = $this->getOrAnalyze($request);

        return Inertia::render('Dashboard/Overview', [
            'metrics' => $result->metrics,
            'recommendations' => array_slice($result->recommendations, 0, 10),
            'generatedAt' => $result->generatedAt,
        ]);
    }

    public function components(Request $request): Response
    {
        $result = $this->getOrAnalyze($request);
        $components = $result->data['components'] ?? [];

        return Inertia::render('Dashboard/Components', [
            'components' => $components,
            'search' => $request->get('search', ''),
        ]);
    }

    public function graphs(Request $request): Response
    {
        $result = $this->getOrAnalyze($request);
        $context = $this->buildContext($result);
        $graph = $this->graphBuilder->build($context);
        $relationships = $this->relationshipMapper->map($context);

        return Inertia::render('Dashboard/Graphs', [
            'dependencyGraph' => $this->visualizer->toMermaid($graph),
            'erDiagram' => $this->visualizer->toErDiagram($relationships),
            'graphData' => $graph,
        ]);
    }

    public function codeVisualization(Request $request): Response
    {
        $result = $this->getOrAnalyze($request);

        return Inertia::render('Dashboard/CodeVisualization', [
            'visualizations' => $result->data['visualizations'] ?? $this->codeVisualizationService->build($this->buildContext($result)),
        ]);
    }

    public function tests(Request $request): Response
    {
        $result = $this->getOrAnalyze($request);
        $testData = $result->data['test'] ?? [];
        $coverage = $result->metrics['coverage'] ?? [];

        return Inertia::render('Dashboard/Tests', [
            'testData' => $testData,
            'coverage' => $coverage,
        ]);
    }

    public function testGeneration(Request $request): Response
    {
        $result = $this->getOrAnalyze($request);
        $testData = $result->data['test'] ?? [];
        $config = config('project-analyzer.test_generation', []);
        $generatedTests = $this->testGenerationService->buildSuggestions(
            $testData['missing_tests'] ?? [],
            base_path(),
            $config
        );

        return Inertia::render('Dashboard/TestGeneration', [
            'coverage' => $result->metrics['coverage'] ?? [],
            'missingTests' => $testData['missing_tests'] ?? [],
            'generatedTests' => $generatedTests,
            'config' => $config,
        ]);
    }

    public function metrics(Request $request): Response
    {
        $result = $this->getOrAnalyze($request);

        return Inertia::render('Dashboard/Metrics', [
            'metrics' => $result->metrics,
            'complexity' => $result->metrics['complexity'] ?? [],
        ]);
    }

    public function recommendations(Request $request): Response
    {
        $result = $this->getOrAnalyze($request);

        return Inertia::render('Dashboard/Recommendations', [
            'recommendations' => $result->recommendations,
        ]);
    }

    public function insights(Request $request): Response
    {
        $result = $this->getOrAnalyze($request);
        $recommendations = $result->recommendations;

        return Inertia::render('Dashboard/Insights', [
            'recommendations' => $recommendations,
            'security' => $result->data['security'] ?? [],
            'cost' => $result->data['cost'] ?? [],
            'summary' => [
                'security' => count(array_filter($recommendations, fn ($item) => ($item['category'] ?? null) === 'security')),
                'enhancement' => count(array_filter($recommendations, fn ($item) => ($item['category'] ?? null) === 'enhancement')),
                'cost' => count(array_filter($recommendations, fn ($item) => ($item['category'] ?? null) === 'cost')),
            ],
        ]);
    }

    public function autoFix(Request $request): Response
    {
        $result = $this->getOrAnalyze($request);

        return Inertia::render('Dashboard/AutoFix', [
            'candidates' => $this->autoFixService->buildCandidates($result->data, base_path()),
        ]);
    }

    public function settings(): Response
    {
        return Inertia::render('Dashboard/Settings', [
            'config' => config('project-analyzer'),
        ]);
    }

    public function validation(Request $request): Response
    {
        $result = $this->getOrAnalyze($request);

        return Inertia::render('Dashboard/Validation', [
            'validation' => $result->data['validation'] ?? $this->validationService->validateEnvironment(base_path(), config('project-analyzer', [])),
        ]);
    }

    public function export(Request $request): \Illuminate\Http\JsonResponse
    {
        $format = $request->get('format', 'json');
        $result = $this->getOrAnalyze($request);
        $exporter = app(\ProjectAnalyzer\Generators\ReportExporter::class);
        $path = $exporter->export($result, $format);

        return response()->json(['path' => $path, 'format' => $format]);
    }

    public function generateTests(Request $request): \Illuminate\Http\JsonResponse
    {
        $result = $this->engine->analyze([
            'quick' => false,
        ]);

        $config = array_merge(
            config('project-analyzer.test_generation', []),
            ['framework' => $request->string('framework')->toString() ?: config('project-analyzer.test_generation.framework', 'pest')]
        );

        $generatedTests = $this->testGenerationService->buildSuggestions(
            $result->data['test']['missing_tests'] ?? [],
            base_path(),
            $config
        );

        $summary = $this->testGenerationService->writeFiles(
            $generatedTests,
            base_path(),
            $request->boolean('force', false)
        );

        return response()->json($summary);
    }

    public function applyAutoFixes(Request $request): \Illuminate\Http\JsonResponse
    {
        $result = $this->engine->analyze([
            'quick' => false,
        ]);

        $candidates = $this->autoFixService->buildCandidates($result->data, base_path());
        $summary = $this->autoFixService->apply(
            $candidates,
            base_path(),
            $request->boolean('force', false)
        );

        return response()->json($summary);
    }

    private function getOrAnalyze(Request $request): \ProjectAnalyzer\Analysis\Result
    {
        return $this->engine->analyze([
            'quick' => $request->boolean('quick', true),
        ]);
    }

    private function buildContext(\ProjectAnalyzer\Analysis\Result $result): \ProjectAnalyzer\Analysis\Context
    {
        return new \ProjectAnalyzer\Analysis\Context(
            config('project-analyzer', []),
            base_path(),
            results: $result->data,
        );
    }
}
