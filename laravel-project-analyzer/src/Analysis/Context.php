<?php

namespace ProjectAnalyzer\Analysis;

class Context
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $paths
     * @param  array<int, string>  $exclude
     * @param  array<int, array<string, mixed>>  $classes
     * @param  array<int, array<string, mixed>>  $files
     */
    public function __construct(
        public readonly array $config,
        public readonly string $basePath,
        public readonly array $paths = [],
        public readonly array $exclude = [],
        public array $classes = [],
        public array $files = [],
        public array $results = [],
    ) {}

    public function addResult(string $analyzer, array $data): void
    {
        $this->results[$analyzer] = $data;
    }

    public function getResult(string $analyzer): ?array
    {
        return $this->results[$analyzer] ?? null;
    }

    public function mergeResults(array $results): void
    {
        $this->results = array_merge($this->results, $results);
    }
}
