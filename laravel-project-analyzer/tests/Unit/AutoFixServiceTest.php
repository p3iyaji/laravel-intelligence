<?php

use ProjectAnalyzer\Fixes\AutoFixService;
use ProjectAnalyzer\Testing\TestGenerationService;

describe('AutoFixService', function () {
    it('builds supported candidates from analysis data', function () {
        $service = new AutoFixService(new TestGenerationService);
        $candidates = $service->buildCandidates([
            'test' => [
                'missing_tests' => [
                    [
                        'class' => 'App\\Services\\BillingService',
                        'name' => 'BillingService',
                        'suggested_path' => 'tests/Unit/BillingServiceTest.php',
                        'suggestion' => 'Create test for BillingService',
                    ],
                ],
            ],
            'security' => [
                'findings' => [
                    [
                        'type' => 'superglobal_usage',
                        'file' => 'app/Http/Controllers/LegacyController.php',
                        'message' => 'Direct superglobal access detected',
                    ],
                ],
            ],
            'service' => [
                'services' => [
                    [
                        'fqn' => 'App\\Services\\BillingService',
                        'file' => 'app/Services/BillingService.php',
                        'has_interface' => false,
                        'methods' => [
                            ['name' => 'charge', 'return_type' => 'bool'],
                        ],
                    ],
                ],
            ],
        ], '/tmp/project');

        expect($candidates)->toHaveCount(3);
    });

    it('applies supported fixes to a temporary project', function () {
        $basePath = sys_get_temp_dir().'/project-analyzer-auto-fix-'.uniqid();
        mkdir($basePath.'/app/Services', 0755, true);
        mkdir($basePath.'/app/Http/Controllers', 0755, true);
        mkdir($basePath.'/tests', 0755, true);

        file_put_contents($basePath.'/app/Services/BillingService.php', <<<'PHP'
<?php

namespace App\Services;

class BillingService
{
    public function charge(): bool
    {
        return true;
    }
}
PHP);

        file_put_contents($basePath.'/app/Http/Controllers/LegacyController.php', <<<'PHP'
<?php

namespace App\Http\Controllers;

class LegacyController
{
    public function index(): string
    {
        return $_GET['filter'];
    }
}
PHP);

        $service = new AutoFixService(new TestGenerationService);
        $candidates = $service->buildCandidates([
            'test' => [
                'missing_tests' => [
                    [
                        'class' => 'App\\Services\\BillingService',
                        'name' => 'BillingService',
                        'type' => 'service',
                        'suggested_suite' => 'unit',
                        'suggested_path' => 'tests/Unit/BillingServiceTest.php',
                        'suggestion' => 'Create test for BillingService',
                    ],
                ],
            ],
            'security' => [
                'findings' => [
                    [
                        'type' => 'superglobal_usage',
                        'file' => 'app/Http/Controllers/LegacyController.php',
                        'message' => 'Direct superglobal access detected',
                    ],
                ],
            ],
            'service' => [
                'services' => [
                    [
                        'fqn' => 'App\\Services\\BillingService',
                        'file' => 'app/Services/BillingService.php',
                        'has_interface' => false,
                        'methods' => [
                            ['name' => '__construct', 'return_type' => null],
                            ['name' => 'charge', 'return_type' => 'bool'],
                        ],
                    ],
                ],
            ],
        ], $basePath);

        $summary = $service->apply($candidates, $basePath, false);

        expect($summary['applied_count'])->toBeGreaterThanOrEqual(2);
        expect(file_exists($basePath.'/tests/Unit/BillingServiceTest.php'))->toBeTrue();
        expect(file_exists($basePath.'/app/Services/BillingServiceInterface.php'))->toBeTrue();
        expect(file_get_contents($basePath.'/app/Http/Controllers/LegacyController.php'))->toContain("request()->query('filter')");

        unlink($basePath.'/tests/Unit/BillingServiceTest.php');
        unlink($basePath.'/app/Services/BillingServiceInterface.php');
        unlink($basePath.'/app/Services/BillingService.php');
        unlink($basePath.'/app/Http/Controllers/LegacyController.php');
        rmdir($basePath.'/tests/Unit');
        rmdir($basePath.'/tests');
        rmdir($basePath.'/app/Services');
        rmdir($basePath.'/app/Http/Controllers');
        rmdir($basePath.'/app/Http');
        rmdir($basePath.'/app');
        rmdir($basePath);
    });
});
