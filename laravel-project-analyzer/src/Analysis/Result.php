<?php

namespace ProjectAnalyzer\Analysis;

class Result
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $metrics
     * @param  array<int, array<string, mixed>>  $recommendations
     */
    public function __construct(
        public readonly array $data,
        public readonly array $metrics = [],
        public readonly array $recommendations = [],
        public readonly ?string $generatedAt = null,
    ) {}

    public static function fromContext(Context $context, array $metrics = [], array $recommendations = []): self
    {
        return new self(
            data: $context->results,
            metrics: $metrics,
            recommendations: $recommendations,
            generatedAt: now()->toIso8601String(),
        );
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'metrics' => $this->metrics,
            'recommendations' => $this->recommendations,
            'generated_at' => $this->generatedAt,
        ];
    }
}
