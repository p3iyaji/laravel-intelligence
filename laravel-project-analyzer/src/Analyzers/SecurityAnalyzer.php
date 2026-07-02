<?php

namespace ProjectAnalyzer\Analyzers;

use ProjectAnalyzer\Analysis\Context;

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
                if (preg_match('/\b'.preg_quote($func, '/').'\s*\(/', $content)) {
                    $findings[] = [
                        'type' => 'dangerous_function',
                        'severity' => 'high',
                        'function' => $func,
                        'file' => $file['path'] ?? $path,
                        'message' => "Dangerous function '{$func}' detected",
                    ];
                }
            }

            if (preg_match('/DB::raw\s*\(/', $content) && ! str_contains($path, 'tests/')) {
                $findings[] = [
                    'type' => 'sql_injection_risk',
                    'severity' => 'medium',
                    'file' => $file['path'] ?? $path,
                    'message' => 'Raw SQL query detected - review for SQL injection risks',
                ];
            }

            if (preg_match('/\$_(GET|POST|REQUEST)\[/', $content)) {
                $findings[] = [
                    'type' => 'superglobal_usage',
                    'severity' => 'low',
                    'file' => $file['path'] ?? $path,
                    'message' => 'Direct superglobal access detected',
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
}
