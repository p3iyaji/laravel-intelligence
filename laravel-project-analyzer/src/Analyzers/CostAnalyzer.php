<?php

namespace ProjectAnalyzer\Analyzers;

use ProjectAnalyzer\Analysis\Context;

class CostAnalyzer extends AbstractAnalyzer
{
    public function getName(): string
    {
        return 'cost';
    }

    public function analyze(Context $context): array
    {
        $hotspots = [];

        foreach ($context->files as $file) {
            $path = $file['absolute_path'] ?? null;
            if (! $path || ! file_exists($path)) {
                continue;
            }

            $content = file_get_contents($path);
            if ($content === false) {
                continue;
            }

            $filePath = (string) ($file['path'] ?? $path);

            $this->detectPattern($hotspots, $content, $filePath, '/(::all|->all)\s*\(/', 'medium', 4, 'Loads complete collections into memory');
            $this->detectPattern($hotspots, $content, $filePath, '/->get\s*\(/', 'medium', 3, 'Query result hydration may become expensive on large datasets');
            $this->detectPattern($hotspots, $content, $filePath, '/DB::raw\s*\(/', 'medium', 4, 'Raw database expressions may bypass query builder optimizations');
            $this->detectPattern($hotspots, $content, $filePath, '/Http::(get|post|put|delete|retry)\s*\(/', 'high', 5, 'Network request detected in application flow');
            $this->detectPattern($hotspots, $content, $filePath, '/foreach\s*\(.+\)\s*\{[\s\S]{0,240}foreach\s*\(/', 'high', 6, 'Nested loops may scale poorly with larger data sets');
        }

        return [
            'total_hotspots' => count($hotspots),
            'estimated_score' => array_sum(array_column($hotspots, 'score')),
            'high_cost_hotspots' => count(array_filter($hotspots, fn ($hotspot) => $hotspot['severity'] === 'high')),
            'hotspots' => $hotspots,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $hotspots
     */
    private function detectPattern(array &$hotspots, string $content, string $filePath, string $pattern, string $severity, int $score, string $message): void
    {
        if (! preg_match($pattern, $content)) {
            return;
        }

        $hotspots[] = [
            'file' => $filePath,
            'severity' => $severity,
            'score' => $score,
            'message' => $message,
            'estimated_cost' => $this->estimatedCostLabel($score),
        ];
    }

    private function estimatedCostLabel(int $score): string
    {
        return match (true) {
            $score >= 6 => 'high',
            $score >= 4 => 'medium',
            default => 'low',
        };
    }
}
