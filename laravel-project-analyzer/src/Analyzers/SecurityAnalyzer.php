<?php

namespace ProjectAnalyzer\Analyzers;

use ProjectAnalyzer\Analysis\Context;
use ProjectAnalyzer\Support\SourceLineDetector;

class SecurityAnalyzer extends AbstractAnalyzer
{
    private const DANGEROUS_FUNCTIONS = [
        'eval', 'exec', 'shell_exec', 'system', 'passthru',
        'proc_open', 'popen', 'unserialize', 'extract',
    ];

    public function getName(): string
    {
        return 'security';
    }

    public function analyze(Context $context): array
    {
        $findings = [];

        foreach ($context->files as $file) {
            $path = $file['absolute_path'] ?? null;
            if (! $path || ! file_exists($path)) {
                continue;
            }

            $content = file_get_contents($path);
            if ($content === false) {
                continue;
            }

            foreach (self::DANGEROUS_FUNCTIONS as $func) {
                $pattern = '/\b'.preg_quote($func, '/').'\s*\(/';
                $lines = SourceLineDetector::findPatternLines($content, $pattern);

                if ($lines === []) {
                    continue;
                }

                $findings[] = [
                    'type' => 'dangerous_function',
                    'severity' => 'high',
                    'function' => $func,
                    'file' => $file['path'] ?? $path,
                    'line' => $lines[0],
                    'lines' => $lines,
                    'message' => "Dangerous function '{$func}' detected",
                    'suggestion' => "Remove or replace {$func}() with a safer API. Avoid dynamic code execution in application code.",
                ];
            }

            if (! $this->isTestFile($context->basePath, $path, (string) ($file['path'] ?? ''))) {
                $rawLines = SourceLineDetector::findPatternLines($content, '/DB::raw\s*\(/');

                if ($rawLines !== []) {
                    $findings[] = [
                        'type' => 'sql_injection_risk',
                        'severity' => 'medium',
                        'file' => $file['path'] ?? $path,
                        'line' => $rawLines[0],
                        'lines' => $rawLines,
                        'message' => 'Raw SQL query detected - review for SQL injection risks',
                        'suggestion' => 'Use query bindings via DB::select() or the query builder instead of DB::raw() with dynamic input.',
                    ];
                }
            }

            $superglobalLines = SourceLineDetector::findPatternLines($content, '/\$_(GET|POST|REQUEST)\[/');

            if ($superglobalLines !== []) {
                $findings[] = [
                    'type' => 'superglobal_usage',
                    'severity' => 'low',
                    'file' => $file['path'] ?? $path,
                    'line' => $superglobalLines[0],
                    'lines' => $superglobalLines,
                    'message' => 'Direct superglobal access detected',
                    'suggestion' => 'Inject Illuminate\\Http\\Request or use request() helper instead of direct $_GET/$_POST/$_REQUEST access.',
                ];
            }
        }

        return [
            'total_findings' => count($findings),
            'high_severity' => count(array_filter($findings, fn ($f) => $f['severity'] === 'high')),
            'medium_severity' => count(array_filter($findings, fn ($f) => $f['severity'] === 'medium')),
            'low_severity' => count(array_filter($findings, fn ($f) => $f['severity'] === 'low')),
            'findings' => $findings,
        ];
    }

    private function isTestFile(string $basePath, ?string $absolutePath, string $relativePath): bool
    {
        $normalizedRelative = str_replace('\\', '/', ltrim($relativePath, '/'));

        if (str_starts_with($normalizedRelative, 'tests/')) {
            return true;
        }

        if ($absolutePath === null) {
            return false;
        }

        $resolvedBase = realpath($basePath);
        $resolvedPath = realpath($absolutePath);
        $testsDirectory = ($resolvedBase ?: rtrim($basePath, '/\\')).'/tests';
        $resolvedTestsDirectory = realpath($testsDirectory) ?: $testsDirectory;

        return $resolvedPath !== false
            && str_starts_with($resolvedPath, $resolvedTestsDirectory);
    }
}
