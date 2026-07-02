<?php

namespace ProjectAnalyzer\Contracts;

interface CollectorInterface
{
    /**
     * @return array<int, mixed>
     */
    public function collect(): array;

    public function getName(): string;
}
