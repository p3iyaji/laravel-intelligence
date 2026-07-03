<?php

use ProjectAnalyzer\Support\DashboardAssets;

describe('DashboardAssets', function () {
    it('loads manifest from package build directory', function () {
        $manifest = DashboardAssets::manifest();

        expect($manifest)->not->toBeNull();
        expect($manifest)->toHaveKey('resources/assets/js/app.js');
        expect(DashboardAssets::assetsAreAvailable())->toBeTrue();
    });

    it('finds manifest in package public build path', function () {
        $path = DashboardAssets::manifestPath();

        expect($path)->not->toBe('');
        expect(file_exists($path))->toBeTrue();
    });
});
