<?php

namespace ProjectAnalyzer\Parsing;

use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class PhpParserFactory
{
    private static ?Parser $parser = null;

    public static function create(): Parser
    {
        if (self::$parser === null) {
            $factory = new ParserFactory;

            self::$parser = method_exists($factory, 'createForNewestSupportedVersion')
                ? $factory->createForNewestSupportedVersion()
                : $factory->create(ParserFactory::PREFER_PHP7);
        }

        return self::$parser;
    }

    /**
     * @return array<int, \PhpParser\Node\Stmt>|null
     */
    public static function parseFile(string $path): ?array
    {
        if (! file_exists($path)) {
            return null;
        }

        $code = file_get_contents($path);

        if ($code === false) {
            return null;
        }

        try {
            return self::create()->parse($code);
        } catch (\PhpParser\Error) {
            return null;
        }
    }

    public static function createTraverser(): NodeTraverser
    {
        return new NodeTraverser;
    }
}
