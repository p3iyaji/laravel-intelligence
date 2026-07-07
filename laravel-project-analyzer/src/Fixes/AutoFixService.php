<?php

namespace ProjectAnalyzer\Fixes;

use Illuminate\Support\Str;
use ProjectAnalyzer\Testing\TestGenerationService;

class AutoFixService
{
    public function __construct(
        private readonly TestGenerationService $testGenerationService,
    ) {}

    /**
     * @param  array<string, mixed>  $analysisData
     * @return array<int, array<string, mixed>>
     */
    public function buildCandidates(array $analysisData, string $basePath): array
    {
        $candidates = [];

        foreach ($analysisData['test']['missing_tests'] ?? [] as $missingTest) {
            $candidates[] = [
                'id' => 'test:'.($missingTest['suggested_path'] ?? $missingTest['class'] ?? uniqid()),
                'action' => 'generate_test',
                'category' => 'testing',
                'title' => 'Generate missing test stub',
                'description' => $missingTest['suggestion'] ?? 'Generate a test stub.',
                'file' => $missingTest['suggested_path'] ?? null,
                'supported' => true,
                'payload' => $missingTest,
            ];
        }

        foreach ($analysisData['security']['findings'] ?? [] as $finding) {
            if (($finding['type'] ?? null) !== 'superglobal_usage') {
                continue;
            }

            $candidates[] = [
                'id' => 'superglobal:'.($finding['file'] ?? uniqid()),
                'action' => 'replace_superglobal',
                'category' => 'security',
                'title' => 'Replace direct superglobal access',
                'description' => $finding['message'] ?? 'Replace direct superglobal access with request helpers.',
                'file' => $finding['file'] ?? null,
                'supported' => true,
                'payload' => $finding,
            ];
        }

        foreach ($analysisData['service']['services'] ?? [] as $service) {
            if (($service['has_interface'] ?? true) || empty($service['methods'])) {
                continue;
            }

            $candidates[] = [
                'id' => 'interface:'.($service['fqn'] ?? uniqid()),
                'action' => 'create_interface',
                'category' => 'enhancement',
                'title' => 'Create service interface',
                'description' => 'Create an interface and update the service to implement it.',
                'file' => $service['file'] ?? null,
                'supported' => true,
                'payload' => $service,
            ];
        }

        return $candidates;
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidates
     * @return array<string, mixed>
     */
    public function apply(array $candidates, string $basePath, bool $force = false): array
    {
        $applied = [];
        $skipped = [];

        foreach ($candidates as $candidate) {
            $success = match ($candidate['action'] ?? null) {
                'generate_test' => $this->applyGeneratedTest($candidate, $basePath, $force),
                'replace_superglobal' => $this->applySuperglobalReplacement($candidate, $basePath),
                'create_interface' => $this->applyInterfaceCreation($candidate, $basePath, $force),
                default => false,
            };

            if ($success) {
                $applied[] = $candidate['id'];
            } else {
                $skipped[] = $candidate['id'];
            }
        }

        return [
            'applied' => $applied,
            'skipped' => $skipped,
            'applied_count' => count($applied),
            'skipped_count' => count($skipped),
        ];
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function applyGeneratedTest(array $candidate, string $basePath, bool $force): bool
    {
        $payload = $candidate['payload'] ?? [];
        if (! is_array($payload)) {
            return false;
        }

        $generated = $this->testGenerationService->buildSuggestions(
            [$payload],
            $basePath,
            $this->testGenerationConfig()
        );

        $result = $this->testGenerationService->writeFiles($generated, $basePath, $force);

        return $result['written_count'] > 0 || ($force && $result['skipped_count'] === 0);
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function applySuperglobalReplacement(array $candidate, string $basePath): bool
    {
        $relativePath = $candidate['file'] ?? null;
        if (! is_string($relativePath) || $relativePath === '') {
            return false;
        }

        $absolutePath = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$relativePath;
        if (! file_exists($absolutePath)) {
            return false;
        }

        $content = file_get_contents($absolutePath);
        if ($content === false) {
            return false;
        }

        $updated = preg_replace('/\$_GET\[[\'"]([^\'"]+)[\'"]\]/', 'request()->query(\'$1\')', $content);
        $updated = preg_replace('/\$_POST\[[\'"]([^\'"]+)[\'"]\]/', 'request()->input(\'$1\')', $updated ?? $content);
        $updated = preg_replace('/\$_REQUEST\[[\'"]([^\'"]+)[\'"]\]/', 'request()->input(\'$1\')', $updated ?? $content);

        if ($updated === null || $updated === $content) {
            return false;
        }

        file_put_contents($absolutePath, $updated);

        return true;
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function applyInterfaceCreation(array $candidate, string $basePath, bool $force): bool
    {
        $service = $candidate['payload'] ?? [];
        if (! is_array($service)) {
            return false;
        }

        $fqn = (string) ($service['fqn'] ?? '');
        $sourceFile = (string) ($service['file'] ?? '');
        if ($fqn === '' || $sourceFile === '') {
            return false;
        }

        $absoluteSource = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$sourceFile;
        if (! file_exists($absoluteSource)) {
            return false;
        }

        $className = Str::afterLast($fqn, '\\');
        $namespace = Str::beforeLast($fqn, '\\');
        $interfaceName = $className.'Interface';
        $interfacePath = dirname($absoluteSource).DIRECTORY_SEPARATOR.$interfaceName.'.php';

        if (file_exists($interfacePath) && ! $force) {
            return false;
        }

        $methods = [];
        foreach ($service['methods'] ?? [] as $method) {
            $name = $method['name'] ?? null;
            if ($name === null || $name === '__construct') {
                continue;
            }

            $returnType = $method['return_type'] ?? 'mixed';
            $methods[] = '    public function '.$name.'(...$args): '.($returnType ?: 'mixed').';';
        }

        $interfaceContents = "<?php\n\nnamespace {$namespace};\n\ninterface {$interfaceName}\n{\n";
        $interfaceContents .= $methods === [] ? "    // TODO: define service contract methods.\n" : implode("\n", $methods)."\n";
        $interfaceContents .= "}\n";

        file_put_contents($interfacePath, $interfaceContents);

        $sourceContents = file_get_contents($absoluteSource);
        if ($sourceContents === false) {
            return false;
        }

        if (! str_contains($sourceContents, 'implements '.$interfaceName)) {
            $updated = preg_replace_callback(
                '/class\s+'.preg_quote($className, '/').'(\s+extends\s+[^\s{]+)?(\s+implements\s+[^{]+)?/',
                fn ($matches) => isset($matches[2]) && $matches[2] !== ''
                    ? $matches[0].', '.$interfaceName
                    : 'class '.$className.($matches[1] ?? '').' implements '.$interfaceName,
                $sourceContents,
                1
            );

            if (is_string($updated)) {
                file_put_contents($absoluteSource, $updated);
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function testGenerationConfig(): array
    {
        if (function_exists('app') && app()->bound('config')) {
            $config = config('project-analyzer.test_generation', []);

            return is_array($config) ? $config : [];
        }

        return [
            'framework' => 'pest',
            'paths' => [
                'unit' => 'tests/Unit',
                'feature' => 'tests/Feature',
            ],
        ];
    }
}
