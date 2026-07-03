<?php

use ProjectAnalyzer\Parsing\ClassVisitor;
use ProjectAnalyzer\Parsing\PhpParserFactory;

describe('ClassVisitor', function () {
    it('extracts class metadata from ast', function () {
        $path = __DIR__.'/../Fixtures/app/Models/User.php';
        $ast = PhpParserFactory::parseFile($path);

        $visitor = new ClassVisitor('app/Models/User.php', 'App\Models');
        $traverser = PhpParserFactory::createTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $classes = $visitor->getClasses();

        expect($classes)->toHaveCount(1);
        expect($classes[0]['fqn'])->toBe('App\Models\User');
        expect($classes[0]['type'])->toBe('class');
    });

    it('formats union, nullable, and intersection return types', function () {
        $path = __DIR__.'/../Fixtures/app/Models/TypedModel.php';
        $ast = PhpParserFactory::parseFile($path);

        $visitor = new ClassVisitor('app/Models/TypedModel.php', 'App\Models');
        $traverser = PhpParserFactory::createTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $methods = collect($visitor->getClasses()[0]['methods'])->keyBy('name');

        expect($methods['findById']['return_type'])->toBe('self|null');
        expect($methods['getTags']['return_type'])->toBe('array|Collection');
        expect($methods['resolve']['return_type'])->toBe('Model&JsonSerializable');
    });
});

describe('TypeFormatter', function () {
    it('returns null for null input', function () {
        expect(\ProjectAnalyzer\Parsing\TypeFormatter::format(null))->toBeNull();
    });
});

describe('Contracts', function () {
    it('defines plugin interface methods', function () {
        $plugin = new class implements \ProjectAnalyzer\Contracts\PluginInterface
        {
            public function getName(): string
            {
                return 'test-plugin';
            }

            public function register(): void {}
        };

        expect($plugin->getName())->toBe('test-plugin');
    });
});

describe('ReportExporter errors', function () {
    it('throws for unsupported format', function () {
        $exporter = new \ProjectAnalyzer\Generators\ReportExporter(
            new \ProjectAnalyzer\Graph\DependencyGraphBuilder,
            new \ProjectAnalyzer\Graph\RelationshipMapper,
        );

        $result = new \ProjectAnalyzer\Analysis\Result([
            'graph' => ['nodes' => [], 'edges' => []],
        ]);

        expect(fn () => $exporter->export($result, 'pdf'))
            ->toThrow(\InvalidArgumentException::class);
    });
});
