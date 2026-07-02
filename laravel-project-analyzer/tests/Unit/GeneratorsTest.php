<?php

use ProjectAnalyzer\Analysis\Result;
use ProjectAnalyzer\Generators\HtmlGenerator;
use ProjectAnalyzer\Generators\JsonGenerator;
use ProjectAnalyzer\Generators\MarkdownGenerator;
use ProjectAnalyzer\Graph\GraphVisualizer;
use ProjectAnalyzer\Recommendations\RecommendationEngine;

describe('JsonGenerator', function () {
    it('generates json report', function () {
        $result = new Result(['class' => ['total' => 5]], ['overall' => 85]);
        $generator = new JsonGenerator;

        $output = $generator->report($result);

        expect($generator->getFormat())->toBe('json');
        expect($generator->getFileExtension())->toBe('json');

        $decoded = json_decode($output, true);
        expect($decoded['metrics']['overall'])->toBe(85);
    });
});

describe('MarkdownGenerator', function () {
    it('generates markdown report', function () {
        $result = new Result(
            ['components' => ['models' => [['fqn' => 'App\Models\User']]]],
            ['overall' => 85, 'testability' => 70, 'statistics' => ['total_classes' => 10]],
            [['priority' => 'high', 'title' => 'Test', 'description' => 'Add tests']],
            '2024-01-01'
        );

        $generator = new MarkdownGenerator(new GraphVisualizer);
        $output = $generator->report($result);

        expect($generator->getFormat())->toBe('markdown');
        expect($output)->toContain('# Project Analysis Report');
        expect($output)->toContain('Health Overview');
    });
});

describe('HtmlGenerator', function () {
    it('generates html report', function () {
        $result = new Result(
            [],
            ['overall' => 85, 'testability' => 70, 'statistics' => []],
            [],
            '2024-01-01'
        );

        $generator = new HtmlGenerator(new GraphVisualizer);
        $output = $generator->report($result);

        expect($generator->getFormat())->toBe('html');
        expect($output)->toContain('<!DOCTYPE html>');
        expect($output)->toContain('Project Analysis Report');
    });
});

describe('RecommendationEngine', function () {
    it('generates recommendations from context', function () {
        $context = new \ProjectAnalyzer\Analysis\Context([], '/base');
        $context->addResult('test', [
            'missing_tests' => [
                ['class' => 'App\\Services\\Foo', 'type' => 'service', 'suggestion' => 'Create test for Foo'],
            ],
        ]);
        $context->addResult('security', [
            'findings' => [
                ['severity' => 'high', 'message' => 'Dangerous function detected', 'file' => 'app/test.php'],
            ],
        ]);

        $engine = new RecommendationEngine;
        $recommendations = $engine->generate($context);

        expect($recommendations)->not->toBeEmpty();
        expect($recommendations[0]['category'])->toBeIn(['testing', 'security']);
    });
});
