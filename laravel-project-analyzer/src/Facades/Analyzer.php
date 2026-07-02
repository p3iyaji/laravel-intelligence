<?php

namespace ProjectAnalyzer\Facades;

use Illuminate\Support\Facades\Facade;
use ProjectAnalyzer\Plugins\PluginManager;

/**
 * @method static void register(\ProjectAnalyzer\Contracts\AnalyzerInterface|\ProjectAnalyzer\Contracts\ReporterInterface|\ProjectAnalyzer\Contracts\PluginInterface $plugin)
 * @method static void registerMany(array $plugins)
 *
 * @see PluginManager
 */
class Analyzer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PluginManager::class;
    }
}
