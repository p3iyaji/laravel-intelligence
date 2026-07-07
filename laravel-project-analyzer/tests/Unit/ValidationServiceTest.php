<?php

use ProjectAnalyzer\Validation\ValidationService;

describe('ValidationService', function () {
    it('validates supported generation options', function () {
        $service = new ValidationService;
        $result = $service->validateGenerationOptions([
            'framework' => 'pest',
            'base_path' => sys_get_temp_dir(),
        ]);

        expect($result['failed'])->toBe(0);
        expect($result['status'])->toBe('passed');
    });

    it('reports invalid generation options', function () {
        $service = new ValidationService;
        $result = $service->validateGenerationOptions([
            'framework' => 'unknown',
            'base_path' => '/definitely/missing/path',
        ]);

        expect($result['failed'])->toBeGreaterThan(0);
        expect($result['status'])->toBe('failed');
    });
});
