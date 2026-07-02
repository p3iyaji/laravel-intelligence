<?php

namespace ProjectAnalyzer\Contracts;

interface PluginInterface
{
    public function getName(): string;

    public function register(): void;
}
