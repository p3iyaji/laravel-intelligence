<?php

namespace ProjectAnalyzer\Generators;

use ProjectAnalyzer\Analysis\Result;
use ProjectAnalyzer\Contracts\ReporterInterface;
use ProjectAnalyzer\Graph\GraphVisualizer;

class HtmlGenerator implements ReporterInterface
{
    public function __construct(
        private readonly GraphVisualizer $visualizer,
    ) {}

    public function report(Result $result): mixed
    {
        $data = $result->toArray();
        $metrics = $data['metrics'] ?? [];
        $stats = $metrics['statistics'] ?? [];

        $html = '<!DOCTYPE html><html lang="en"><head>';
        $html .= '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '<title>Project Analysis Report</title>';
        $html .= '<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>';
        $html .= '<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>';
        $html .= '<style>
            body { font-family: system-ui, sans-serif; max-width: 1200px; margin: 0 auto; padding: 2rem; }
            .score-card { display: inline-block; padding: 1.5rem; margin: 0.5rem; border-radius: 8px; background: #f3f4f6; text-align: center; min-width: 120px; }
            .score-value { font-size: 2rem; font-weight: bold; color: #2563eb; }
            table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
            th, td { padding: 0.75rem; border: 1px solid #e5e7eb; text-align: left; }
            th { background: #f9fafb; }
            .priority-high { color: #dc2626; } .priority-medium { color: #d97706; } .priority-low { color: #65a30d; }
        </style></head><body>';

        $html .= '<h1>Project Analysis Report</h1>';
        $html .= '<p>Generated: '.htmlspecialchars($data['generated_at'] ?? '').'</p>';

        $html .= '<h2>Health Scores</h2><div>';
        foreach (['overall', 'testability', 'code_quality', 'architecture', 'security', 'maintainability'] as $key) {
            $value = $metrics[$key] ?? 'N/A';
            $label = ucwords(str_replace('_', ' ', $key));
            $html .= "<div class=\"score-card\"><div class=\"score-value\">{$value}</div><div>{$label}</div></div>";
        }
        $html .= '</div>';

        $html .= '<h2>Statistics</h2><table><tr><th>Metric</th><th>Value</th></tr>';
        foreach ($stats as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            $html .= '<tr><td>'.htmlspecialchars($label).'</td><td>'.htmlspecialchars((string) $value).'</td></tr>';
        }
        $html .= '</table>';

        $html .= '<h2>Recommendations</h2><table><tr><th>Priority</th><th>Category</th><th>Description</th></tr>';
        foreach ($data['recommendations'] ?? [] as $rec) {
            $priority = $rec['priority'] ?? 'low';
            $html .= '<tr>';
            $html .= '<td class="priority-'.$priority.'">'.htmlspecialchars($priority).'</td>';
            $html .= '<td>'.htmlspecialchars($rec['category'] ?? '').'</td>';
            $html .= '<td>'.htmlspecialchars($rec['description'] ?? '').'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        if (isset($data['data']['graph'])) {
            $mermaid = $this->visualizer->toMermaid($data['data']['graph']);
            $html .= '<h2>Dependency Graph</h2><div class="mermaid">'.htmlspecialchars($mermaid).'</div>';
        }

        $html .= '<script>mermaid.initialize({startOnLoad:true});</script></body></html>';

        return $html;
    }

    public function getFormat(): string
    {
        return 'html';
    }

    public function getFileExtension(): string
    {
        return 'html';
    }
}
