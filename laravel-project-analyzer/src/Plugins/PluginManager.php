<?php

namespace ProjectAnalyzer\Plugins;

use ProjectAnalyzer\Contracts\AnalyzerInterface;
use ProjectAnalyzer\Contracts\PluginInterface;
use ProjectAnalyzer\Contracts\ReporterInterface;
use ProjectAnalyzer\Engine\AnalysisEngine;
use ProjectAnalyzer\Generators\ReportExporter;

class PluginManager
{
    /** @var array<int, PluginInterface> */
    private array $plugins = [];

    /** @var array<int, AnalyzerInterface> */
    private array $customAnalyzers = [];

    /** @var array<int, ReporterInterface> */
    private array $customReporters = [];

    public function __construct(
        private readonly AnalysisEngine $engine,
        private readonly ReportExporter $exporter,
    ) {}

    public function register(AnalyzerInterface|ReporterInterface|PluginInterface $plugin): void
    {
        if ($plugin instanceof PluginInterface) {
            $this->plugins[] = $plugin;
            $plugin->register();

            return;
        }

        if ($plugin instanceof AnalyzerInterface) {
            $this->customAnalyzers[] = $plugin;
            $this->engine->registerAnalyzer($plugin);

            return;
        }

        if ($plugin instanceof ReporterInterface) {
            $this->customReporters[] = $plugin;
            $this->exporter->registerReporter($plugin);
        }
    }

    /**
     * @param  array<int, AnalyzerInterface|ReporterInterface|PluginInterface>  $plugins
     */
    public function registerMany(array $plugins): void
    {
        foreach ($plugins as $plugin) {
            $this->register($plugin);
        }
    }

    /**
     * @return array<int, AnalyzerInterface>
     */
    public function getCustomAnalyzers(): array
    {
        return $this->customAnalyzers;
    }

    /**
     * @return array<int, ReporterInterface>
     */
    public function getCustomReporters(): array
    {
        return $this->customReporters;
    }
}
