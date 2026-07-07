<?php

use Illuminate\Support\Facades\Route;
use ProjectAnalyzer\Dashboard\DashboardController;

$prefix = config('project-analyzer.dashboard.route_prefix', 'analyzer');
$middleware = config('project-analyzer.dashboard.middleware', ['web']);

Route::middleware(array_merge($middleware, [\ProjectAnalyzer\Http\Middleware\HandleInertiaRequests::class]))
    ->prefix($prefix)
    ->name('project-analyzer.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/components', [DashboardController::class, 'components'])->name('components');
        Route::get('/components/source', [DashboardController::class, 'componentSource'])->name('components.source');
        Route::get('/graphs', [DashboardController::class, 'graphs'])->name('graphs');
        Route::get('/code-visualization', [DashboardController::class, 'codeVisualization'])->name('code-visualization');
        Route::get('/tests', [DashboardController::class, 'tests'])->name('tests');
        Route::get('/test-generation', [DashboardController::class, 'testGeneration'])->name('test-generation');
        Route::get('/auto-fix', [DashboardController::class, 'autoFix'])->name('auto-fix');
        Route::get('/metrics', [DashboardController::class, 'metrics'])->name('metrics');
        Route::get('/insights', [DashboardController::class, 'insights'])->name('insights');
        Route::get('/recommendations', [DashboardController::class, 'recommendations'])->name('recommendations');
        Route::get('/validation', [DashboardController::class, 'validation'])->name('validation');
        Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
        Route::post('/test-generation/generate', [DashboardController::class, 'generateTests'])->name('test-generation.generate');
        Route::post('/auto-fix/apply', [DashboardController::class, 'applyAutoFixes'])->name('auto-fix.apply');
        Route::post('/export', [DashboardController::class, 'export'])->name('export');
    });
