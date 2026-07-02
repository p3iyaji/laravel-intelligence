<?php

namespace ProjectAnalyzer\Generators;

use ProjectAnalyzer\Analysis\Result;
use ProjectAnalyzer\Graph\DependencyGraphBuilder;
use ProjectAnalyzer\Graph\RelationshipMapper;

class ReportExporter
{
    /** @var array<string, \ProjectAnalyzer\Contracts\ReporterInterface> */
    private array $reporters = [];

    public function __construct(
        private readonly DependencyGraphBuilder $graphBuilder,
        private readonly RelationshipMapper $relationshipMapper,
    ) {}

    public function registerReporter(\ProjectAnalyzer\Contracts\ReporterInterface $reporter): void
    {
        $this->reporters[$reporter->getFormat()] = $reporter;
    }

    public function export(Result $result, string $format, ?string $path = null): string
    {
        $data = $result->toArray();

        if (! isset($data['data']['graph'])) {
            $context = new \ProjectAnalyzer\Analysis\Context(
                config('project-analyzer', []),
                base_path(),
                results: $data['data'],
            );
            $data['data']['graph'] = $this->graphBuilder->build($context);
            $data['data']['relationships'] = $this->relationshipMapper->map($context);
            $result = new Result($data['data'], $data['metrics'], $data['recommendations'], $data['generated_at']);
        }

        $reporter = $this->reporters[$format] ?? null;
        if (! $reporter) {
            throw new \InvalidArgumentException("Unsupported format: {$format}");
        }

        $content = $reporter->report($result);
        $outputPath = $path ?? $this->defaultPath($format, $reporter->getFileExtension());

        $directory = dirname($outputPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($outputPath, $content);

        return $outputPath;
    }

    private function defaultPath(string $format, string $extension): string
    {
        $location = config('project-analyzer.export.location', storage_path('project-analysis'));

        return $location.'/analysis-'.date('Y-m-d-His').'.'.$extension;
    }
}
