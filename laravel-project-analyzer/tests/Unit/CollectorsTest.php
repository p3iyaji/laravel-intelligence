<?php

use ProjectAnalyzer\Analysis\Context;
use ProjectAnalyzer\Analysis\Result;
use ProjectAnalyzer\Collectors\ClassCollector;
use ProjectAnalyzer\Collectors\ComponentCollector;
use ProjectAnalyzer\Collectors\FileCollector;

describe('Context', function () {
    it('stores and retrieves results', function () {
        $context = new Context([], '/base');
        $context->addResult('test', ['foo' => 'bar']);

        expect($context->getResult('test'))->toBe(['foo' => 'bar']);
        expect($context->getResult('missing'))->toBeNull();
    });

    it('merges results', function () {
        $context = new Context([], '/base');
        $context->addResult('a', [1]);
        $context->mergeResults(['b' => [2]]);

        expect($context->results)->toHaveKeys(['a', 'b']);
    });
});

describe('Result', function () {
    it('converts to array', function () {
        $result = new Result(['data' => 'test'], ['score' => 85], [], '2024-01-01');

        $array = $result->toArray();
        expect($array)->toHaveKeys(['data', 'metrics', 'recommendations', 'generated_at']);
        expect($array['metrics']['score'])->toBe(85);
    });

    it('creates from context', function () {
        $context = new Context([], '/base', results: ['class' => ['total' => 5]]);
        $result = Result::fromContext($context, ['overall' => 90]);

        expect($result->data)->toHaveKey('class');
        expect($result->metrics['overall'])->toBe(90);
    });
});

describe('FileCollector', function () {
    it('collects php files from fixture directory', function () {
        $fixturePath = __DIR__.'/../Fixtures';
        $collector = new FileCollector($fixturePath, ['app', 'database', 'routes', 'tests']);

        $files = $collector->collect();

        expect($files)->not->toBeEmpty();
        expect($collector->getName())->toBe('file');

        foreach ($files as $file) {
            expect($file)->toHaveKeys(['path', 'absolute_path', 'size']);
        }
    });
});

describe('ClassCollector', function () {
    it('extracts classes from php files', function () {
        $fixturePath = __DIR__.'/../Fixtures';
        $fileCollector = new FileCollector($fixturePath, ['app']);
        $files = $fileCollector->collect();

        $collector = new ClassCollector($files);
        $classes = $collector->collect();

        expect($classes)->not->toBeEmpty();
        expect($collector->getName())->toBe('class');

        $fqns = array_column($classes, 'fqn');
        expect($fqns)->toContain('App\Models\User');
        expect($fqns)->toContain('App\Http\Controllers\UserController');
    });
});

describe('ComponentCollector', function () {
    it('categorizes components by type', function () {
        $fixturePath = __DIR__.'/../Fixtures';
        $fileCollector = new FileCollector($fixturePath, ['app']);
        $classCollector = new ClassCollector($fileCollector->collect());
        $classes = $classCollector->collect();

        $collector = new ComponentCollector($classes);
        $components = $collector->collect();

        expect($components)->toHaveKeys(['models', 'controllers', 'services']);
        expect($components['models'])->not->toBeEmpty();
        expect($components['controllers'])->not->toBeEmpty();
        expect($components['services'])->not->toBeEmpty();
    });
});
