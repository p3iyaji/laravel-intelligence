<?php

namespace ProjectAnalyzer\Contracts;

use ProjectAnalyzer\Analysis\Result;

interface ReporterInterface
{
    public function report(Result $result): mixed;

    public function getFormat(): string;

    public function getFileExtension(): string;
}
