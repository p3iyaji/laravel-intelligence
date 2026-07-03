<?php

namespace ProjectAnalyzer\Support;

class DashboardAssets
{
    private const ENTRY = 'resources/assets/js/app.js';

    public static function tags(): string
    {
        if (self::isTestingEnvironment()) {
            return '';
        }

        $manifest = self::manifest();

        if ($manifest === null) {
            return '<!-- Project Analyzer: dashboard assets not found. Run: php artisan vendor:publish --tag=project-analyzer-public -->';
        }

        $entry = $manifest[self::ENTRY] ?? null;

        if ($entry === null) {
            return '';
        }

        $baseUrl = self::assetBaseUrl();
        $html = '';

        if (! empty($entry['css'])) {
            foreach ($entry['css'] as $css) {
                $html .= '<link rel="stylesheet" href="'.$baseUrl.'/'.$css.'">'."\n    ";
            }
        }

        if (! empty($entry['file'])) {
            $html .= '<script type="module" src="'.$baseUrl.'/'.$entry['file'].'"></script>';
        }

        return $html;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function manifest(): ?array
    {
        $path = self::manifestPath();

        if (! file_exists($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $manifest = json_decode($contents, true);

        return is_array($manifest) ? $manifest : null;
    }

    public static function manifestPath(): string
    {
        $packageCandidates = [
            self::packagePath('build/manifest.json'),
            self::packagePath('build/.vite/manifest.json'),
        ];

        foreach ($packageCandidates as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        if (self::hasApplication()) {
            $publishedCandidates = [
                public_path('vendor/project-analyzer/build/manifest.json'),
                public_path('vendor/project-analyzer/build/.vite/manifest.json'),
            ];

            foreach ($publishedCandidates as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return self::packagePath('build/.vite/manifest.json');
    }

    public static function assetsAreAvailable(): bool
    {
        $manifest = self::manifest();

        return $manifest !== null && isset($manifest[self::ENTRY]);
    }

    public static function assetBaseUrl(): string
    {
        if (self::hasApplication()) {
            if (file_exists(public_path('vendor/project-analyzer/build/manifest.json'))
                || file_exists(public_path('vendor/project-analyzer/build/.vite/manifest.json'))) {
                return asset('vendor/project-analyzer/build');
            }
        }

        return url('vendor/project-analyzer/build');
    }

    public static function packagePath(string $path = ''): string
    {
        $base = realpath(__DIR__.'/../../public');

        if ($base === false) {
            return '';
        }

        return $path ? $base.'/'.$path : $base;
    }

    public static function publishAssets(): void
    {
        if (! self::hasApplication()) {
            return;
        }

        $source = self::packagePath();
        $destination = public_path('vendor/project-analyzer');

        if (! is_dir($source)) {
            return;
        }

        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        self::copyDirectory($source, $destination);
    }

    private static function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $destination.DIRECTORY_SEPARATOR.$iterator->getSubPathName();

            if ($item->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item->getPathname(), $target);
            }
        }
    }

    private static function hasApplication(): bool
    {
        return function_exists('app') && app()->bound('path.public');
    }

    private static function isTestingEnvironment(): bool
    {
        if (! function_exists('app') || ! app()->bound('env')) {
            return false;
        }

        return app()->environment('testing');
    }
}
