<?php

namespace ProjectAnalyzer\Contracts;

use ProjectAnalyzer\Analysis\Context;

interface AnalyzerInterface
{
    public function analyze(Context $context): array;

    public function getName(): string;

    public function getPriority(): int;

    public function isEnabled(): bool;
}
