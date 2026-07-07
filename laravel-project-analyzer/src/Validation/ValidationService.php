<?php

namespace ProjectAnalyzer\Validation;

class ValidationService
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function validateEnvironment(string $basePath, array $config): array
    {
        $checks = [];

        $checks[] = $this->check(
            'Base path exists',
            is_dir($basePath),
            is_dir($basePath) ? 'Base path is readable.' : 'Base path does not exist.'
        );

        foreach (($config['analysis']['paths'] ?? []) as $path) {
            $fullPath = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$path;
            $checks[] = $this->check(
                'Analysis path: '.$path,
                is_dir($fullPath),
                is_dir($fullPath) ? 'Directory is available for analysis.' : 'Directory is missing; analyzer will skip it.',
                is_dir($fullPath) ? 'passed' : 'warning'
            );
        }

        $exportLocation = (string) ($config['export']['location'] ?? '');
        if ($exportLocation !== '') {
            $checks[] = $this->check(
                'Export directory writable',
                $this->isWritableOrCreatable($exportLocation),
                $this->isWritableOrCreatable($exportLocation)
                    ? 'Reports can be written to the export directory.'
                    : 'Export directory is not writable.',
                $this->isWritableOrCreatable($exportLocation) ? 'passed' : 'failed'
            );
        }

        foreach (($config['test_generation']['paths'] ?? []) as $suite => $path) {
            $fullPath = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$path;
            $checks[] = $this->check(
                'Test generation path: '.$suite,
                $this->isWritableOrCreatable($fullPath),
                $this->isWritableOrCreatable($fullPath)
                    ? 'Generated tests can be written for the '.$suite.' suite.'
                    : 'Generated tests cannot be written for the '.$suite.' suite.',
                $this->isWritableOrCreatable($fullPath) ? 'passed' : 'warning'
            );
        }

        if (function_exists('public_path')) {
            $manifestPath = public_path('vendor/project-analyzer/build/.vite/manifest.json');
            $checks[] = $this->check(
                'Dashboard assets available',
                file_exists($manifestPath),
                file_exists($manifestPath)
                    ? 'Published dashboard assets were found.'
                    : 'Dashboard assets are missing; publish them before using the UI.',
                file_exists($manifestPath) ? 'passed' : 'warning'
            );
        }

        return $this->summarize($checks);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function validateGenerationOptions(array $options): array
    {
        $checks = [];
        $framework = $options['framework'] ?? 'pest';
        $basePath = $options['base_path'] ?? null;

        $checks[] = $this->check(
            'Supported framework',
            in_array($framework, ['pest', 'phpunit'], true),
            in_array($framework, ['pest', 'phpunit'], true)
                ? 'Framework is supported.'
                : 'Framework must be pest or phpunit.',
            in_array($framework, ['pest', 'phpunit'], true) ? 'passed' : 'failed'
        );

        if (is_string($basePath) && $basePath !== '') {
            $checks[] = $this->check(
                'Base path exists',
                is_dir($basePath),
                is_dir($basePath) ? 'Base path exists.' : 'Base path does not exist.',
                is_dir($basePath) ? 'passed' : 'failed'
            );
        }

        return $this->summarize($checks);
    }

    /**
     * @param  array<int, array<string, string|bool>>  $checks
     * @return array<string, mixed>
     */
    private function summarize(array $checks): array
    {
        $failed = count(array_filter($checks, fn ($check) => $check['status'] === 'failed'));
        $warnings = count(array_filter($checks, fn ($check) => $check['status'] === 'warning'));
        $passed = count(array_filter($checks, fn ($check) => $check['status'] === 'passed'));

        return [
            'status' => $failed > 0 ? 'failed' : ($warnings > 0 ? 'warning' : 'passed'),
            'failed' => $failed,
            'warnings' => $warnings,
            'passed' => $passed,
            'checks' => $checks,
        ];
    }

    /**
     * @return array<string, string|bool>
     */
    private function check(string $name, bool $ok, string $message, string $status = 'passed'): array
    {
        return [
            'name' => $name,
            'ok' => $ok,
            'message' => $message,
            'status' => $ok ? $status : ($status === 'passed' ? 'failed' : $status),
        ];
    }

    private function isWritableOrCreatable(string $path): bool
    {
        if (is_dir($path)) {
            return is_writable($path);
        }

        $parent = dirname($path);

        return is_dir($parent) && is_writable($parent);
    }
}
