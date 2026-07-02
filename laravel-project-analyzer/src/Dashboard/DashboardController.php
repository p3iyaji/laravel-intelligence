<?php

namespace ProjectAnalyzer\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use ProjectAnalyzer\Engine\AnalysisEngine;
use ProjectAnalyzer\Graph\DependencyGraphBuilder;
use ProjectAnalyzer\Graph\GraphVisualizer;
use ProjectAnalyzer\Graph\RelationshipMapper;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AnalysisEngine $engine,
        private readonly DependencyGraphBuilder $graphBuilder,
        private readonly RelationshipMapper $relationshipMapper,
        private readonly GraphVisualizer $visualizer,
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

    public function settings(): Response
    {
        return Inertia::render('Dashboard/Settings', [
            'config' => config('project-analyzer'),
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
