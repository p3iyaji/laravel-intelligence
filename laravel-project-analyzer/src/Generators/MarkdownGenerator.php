<?php

namespace ProjectAnalyzer\Generators;

use ProjectAnalyzer\Analysis\Result;
use ProjectAnalyzer\Contracts\ReporterInterface;
use ProjectAnalyzer\Graph\GraphVisualizer;

class MarkdownGenerator implements ReporterInterface
{
    public function __construct(
        private readonly GraphVisualizer $visualizer,
    ) {}

    public function report(Result $result): mixed
    {
        $data = $result->toArray();
        $metrics = $data['metrics'] ?? [];
        $stats = $metrics['statistics'] ?? [];

        $md = "# Project Analysis Report\n\n";
        $md .= "Generated: {$data['generated_at']}\n\n";

        $md .= "## Health Overview\n\n";
        $md .= "| Metric | Score |\n|--------|-------|\n";
        $md .= '| Overall Health | '.($metrics['overall'] ?? 'N/A')." |\n";
        $md .= '| Testability | '.($metrics['testability'] ?? 'N/A')." |\n";
        $md .= '| Code Quality | '.($metrics['code_quality'] ?? 'N/A')." |\n";
        $md .= '| Architecture | '.($metrics['architecture'] ?? 'N/A')." |\n";
        $md .= '| Security | '.($metrics['security'] ?? 'N/A')." |\n\n";

        $md .= "## Project Statistics\n\n";
        foreach ($stats as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            $md .= "- **{$label}**: {$value}\n";
        }

        $md .= "\n## Components\n\n";
        $components = $data['data']['components'] ?? [];
        foreach ($components as $type => $items) {
            if (is_array($items) && count($items) > 0) {
                $md .= "### ".ucfirst($type).' ('.count($items).")\n\n";
                foreach (array_slice($items, 0, 20) as $item) {
                    $md .= '- `'.($item['fqn'] ?? $item['name'] ?? 'unknown')."`\n";
                }
                if (count($items) > 20) {
                    $md .= '- ... and '.(count($items) - 20)." more\n";
                }
                $md .= "\n";
            }
        }

        $md .= "## Recommendations\n\n";
        foreach ($data['recommendations'] ?? [] as $rec) {
            $md .= "### [{$rec['priority']}] {$rec['title']}\n";
            $md .= "{$rec['description']}\n\n";
        }

        if (isset($data['data']['graph'])) {
            $md .= "## Dependency Graph\n\n```mermaid\n";
            $md .= $this->visualizer->toMermaid($data['data']['graph']);
            $md .= "\n```\n";
        }

        return $md;
    }

    public function getFormat(): string
    {
        return 'markdown';
    }

    public function getFileExtension(): string
    {
        return 'md';
    }
}
