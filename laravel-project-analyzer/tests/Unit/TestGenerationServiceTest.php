<?php

use ProjectAnalyzer\Testing\TestGenerationService;

describe('TestGenerationService', function () {
    it('builds pest test stubs from missing tests', function () {
        $service = new TestGenerationService;
        $generated = $service->buildSuggestions([
            [
                'class' => 'App\\Http\\Controllers\\UserController',
                'name' => 'UserController',
                'type' => 'controller',
                'suggested_suite' => 'feature',
                'public_methods' => ['index', 'store'],
                'suggested_cases' => [
                    'add request/response assertions for UserController',
                    'add coverage for UserController::index',
                ],
            ],
        ], '/tmp/project', ['framework' => 'pest']);

        expect($generated)->toHaveCount(1);
        expect($generated[0]['relative_path'])->toBe('tests/Feature/UserControllerTest.php');
        expect($generated[0]['contents'])->toContain("use App\\Http\\Controllers\\UserController;");
        expect($generated[0]['contents'])->toContain('expect(class_exists(UserController::class))->toBeTrue();');
    });

    it('writes generated tests and skips existing files', function () {
        $basePath = sys_get_temp_dir().'/project-analyzer-test-generation-'.uniqid();
        mkdir($basePath.'/tests/Unit', 0755, true);

        $service = new TestGenerationService;
        $generated = [
            [
                'relative_path' => 'tests/Unit/UserServiceTest.php',
                'contents' => "<?php\n\ntest('generated', function () {\n    expect(true)->toBeTrue();\n});\n",
            ],
        ];

        $firstRun = $service->writeFiles($generated, $basePath, false);
        $secondRun = $service->writeFiles($generated, $basePath, false);

        expect($firstRun['written_count'])->toBe(1);
        expect($secondRun['skipped_count'])->toBe(1);
        expect(file_exists($basePath.'/tests/Unit/UserServiceTest.php'))->toBeTrue();

        unlink($basePath.'/tests/Unit/UserServiceTest.php');
        rmdir($basePath.'/tests/Unit');
        rmdir($basePath.'/tests');
        rmdir($basePath);
    });
});
