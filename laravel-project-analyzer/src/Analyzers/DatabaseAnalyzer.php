<?php

namespace ProjectAnalyzer\Analyzers;

use ProjectAnalyzer\Analysis\Context;

class DatabaseAnalyzer extends AbstractAnalyzer
{
    public function getName(): string
    {
        return 'database';
    }

    public function analyze(Context $context): array
    {
        $migrations = [];
        $tables = [];

        foreach ($context->files as $file) {
            $path = $file['path'] ?? '';
            if (! str_contains($path, 'database/migrations/') && ! str_contains($path, 'migrations/')) {
                continue;
            }

            $content = file_get_contents($file['absolute_path'] ?? '');
            if ($content === false) {
                continue;
            }

            $migration = [
                'file' => $path,
                'tables' => [],
                'foreign_keys' => [],
            ];

            if (preg_match_all('/Schema::create\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $tableMatches)) {
                $migration['tables'] = $tableMatches[1];
                $tables = array_merge($tables, $tableMatches[1]);
            }

            if (preg_match_all('/->foreign\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $fkMatches)) {
                $migration['foreign_keys'] = $fkMatches[1];
            }

            $migrations[] = $migration;
        }

        return [
            'total_migrations' => count($migrations),
            'total_tables' => count(array_unique($tables)),
            'tables' => array_values(array_unique($tables)),
            'migrations' => $migrations,
        ];
    }
}
