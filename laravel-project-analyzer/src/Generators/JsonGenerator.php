<?php

namespace ProjectAnalyzer\Generators;

use ProjectAnalyzer\Analysis\Result;
use ProjectAnalyzer\Contracts\ReporterInterface;
use ProjectAnalyzer\Graph\GraphVisualizer;

class JsonGenerator implements ReporterInterface
{
    public function report(Result $result): mixed
    {
        return json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function getFormat(): string
    {
        return 'json';
    }

    public function getFileExtension(): string
    {
        return 'json';
    }
}
