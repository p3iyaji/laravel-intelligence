<?php

namespace ProjectAnalyzer\Collectors;

use ProjectAnalyzer\Contracts\CollectorInterface;
use Symfony\Component\Finder\Finder;

class FileCollector implements CollectorInterface
{
    /**
     * @param  array<int, string>  $paths
     * @param  array<int, string>  $exclude
     */
    public function __construct(
        private readonly string $basePath,
        private readonly array $paths = ['app', 'database', 'routes', 'config', 'tests'],
        private readonly array $exclude = ['vendor', 'storage', 'bootstrap/cache', 'node_modules'],
    ) {}

    public function collect(): array
    {
        $files = [];
        $finder = new Finder;

        $paths = $this->resolvePaths();
        if (empty($paths)) {
            return [];
        }

        $finder->files()->in($paths)->name('*.php');

        foreach ($this->exclude as $pattern) {
            $finder->notPath($pattern);
        }

        foreach ($finder as $file) {
            $files[] = [
                'path' => $file->getRelativePathname(),
                'absolute_path' => $file->getRealPath(),
                'size' => $file->getSize(),
                'modified_at' => $file->getMTime(),
            ];
        }

        return $files;
    }

    public function getName(): string
    {
        return 'file';
    }

    /**
     * @return array<int, string>
     */
    private function resolvePaths(): array
    {
        $resolved = [];

        foreach ($this->paths as $path) {
            $fullPath = $this->basePath.DIRECTORY_SEPARATOR.$path;
            if (is_dir($fullPath)) {
                $resolved[] = $fullPath;
            }
        }

        if (empty($resolved) && is_dir($this->basePath)) {
            $resolved[] = $this->basePath;
        }

        return $resolved;
    }
}
