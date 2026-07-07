<?php

namespace ProjectAnalyzer\Testing;

use Illuminate\Support\Str;

class TestGenerationService
{
    /**
     * @param  array<int, array<string, mixed>>  $missingTests
     * @param  array<string, mixed>  $config
     * @return array<int, array<string, mixed>>
     */
    public function buildSuggestions(array $missingTests, string $basePath, array $config = []): array
    {
        $framework = $config['framework'] ?? 'pest';
        $paths = $config['paths'] ?? [
            'unit' => 'tests/Unit',
            'feature' => 'tests/Feature',
        ];

        $generated = [];

        foreach ($missingTests as $missingTest) {
            $class = (string) ($missingTest['class'] ?? '');
            $name = (string) ($missingTest['name'] ?? Str::afterLast($class, '\\'));
            $suite = (string) ($missingTest['suggested_suite'] ?? $this->defaultSuite((string) ($missingTest['type'] ?? 'service')));
            $directory = (string) ($paths[$suite] ?? 'tests/Unit');
            $relativePath = $directory.'/'.$name.'Test.php';
            $cases = $missingTest['suggested_cases'] ?? [];

            $generated[] = [
                'class' => $class,
                'name' => $name,
                'type' => (string) ($missingTest['type'] ?? 'service'),
                'suite' => $suite,
                'framework' => $framework,
                'relative_path' => $relativePath,
                'absolute_path' => rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$relativePath,
                'exists' => file_exists(rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$relativePath),
                'public_methods' => $missingTest['public_methods'] ?? [],
                'suggested_cases' => $cases,
                'contents' => $framework === 'phpunit'
                    ? $this->buildPhpUnitTemplate($class, $name, $suite, $cases)
                    : $this->buildPestTemplate($class, $name, $cases),
            ];
        }

        return $generated;
    }

    /**
     * @param  array<int, array<string, mixed>>  $generatedTests
     * @return array<string, mixed>
     */
    public function writeFiles(array $generatedTests, string $basePath, bool $force = false): array
    {
        $written = [];
        $skipped = [];

        foreach ($generatedTests as $generatedTest) {
            $relativePath = (string) ($generatedTest['relative_path'] ?? '');
            $absolutePath = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$relativePath;

            if (file_exists($absolutePath) && ! $force) {
                $skipped[] = $relativePath;

                continue;
            }

            $directory = dirname($absolutePath);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($absolutePath, (string) ($generatedTest['contents'] ?? ''));
            $written[] = $relativePath;
        }

        return [
            'written' => $written,
            'skipped' => $skipped,
            'written_count' => count($written),
            'skipped_count' => count($skipped),
        ];
    }

    private function defaultSuite(string $type): string
    {
        return $type === 'controller' ? 'feature' : 'unit';
    }

    /**
     * @param  array<int, string>  $cases
     */
    private function buildPestTemplate(string $class, string $name, array $cases): string
    {
        $caseLines = $this->renderCommentCases($cases);

        return <<<PHP
<?php

use {$class};

describe('{$name}', function () {
    it('needs generated coverage for {$name}', function () {
        expect(class_exists({$name}::class))->toBeTrue();

{$caseLines}
    });
});
PHP;
    }

    /**
     * @param  array<int, string>  $cases
     */
    private function buildPhpUnitTemplate(string $class, string $name, string $suite, array $cases): string
    {
        $namespace = $suite === 'feature' ? 'Tests\\Feature' : 'Tests\\Unit';
        $caseLines = $this->renderCommentCases($cases, '        ');

        return <<<PHP
<?php

namespace {$namespace};

use {$class};
use PHPUnit\\Framework\\TestCase;

class {$name}Test extends TestCase
{
    public function test_it_needs_generated_coverage(): void
    {
        \$this->assertTrue(class_exists({$name}::class));

{$caseLines}
    }
}
PHP;
    }

    /**
     * @param  array<int, string>  $cases
     */
    private function renderCommentCases(array $cases, string $indent = '        '): string
    {
        if ($cases === []) {
            return $indent.'// TODO: add assertions and scenarios for this component.';
        }

        $lines = [];
        foreach ($cases as $case) {
            $lines[] = $indent.'// TODO: '.$case;
        }

        return implode("\n", $lines);
    }
}
