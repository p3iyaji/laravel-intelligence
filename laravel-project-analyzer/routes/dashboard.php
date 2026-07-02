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
        Route::get('/graphs', [DashboardController::class, 'graphs'])->name('graphs');
        Route::get('/tests', [DashboardController::class, 'tests'])->name('tests');
        Route::get('/metrics', [DashboardController::class, 'metrics'])->name('metrics');
        Route::get('/recommendations', [DashboardController::class, 'recommendations'])->name('recommendations');
        Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
        Route::post('/export', [DashboardController::class, 'export'])->name('export');
    });
