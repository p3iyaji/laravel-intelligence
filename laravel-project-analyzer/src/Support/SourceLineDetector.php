<?php

namespace ProjectAnalyzer\Support;

class SourceLineDetector
{
    public static function lineNumber(string $content, int $byteOffset): int
    {
        return substr_count(substr($content, 0, $byteOffset), "\n") + 1;
    }

    /**
     * @return array<int, int>
     */
    public static function findPatternLines(string $content, string $pattern): array
    {
        $lines = [];

        if (! preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $lines;
        }

        foreach ($matches[0] as $match) {
            $lines[] = self::lineNumber($content, $match[1]);
        }

        return array_values(array_unique($lines));
    }

    /**
     * @return array<int, int>
     */
    public static function firstPatternLine(string $content, string $pattern): array
    {
        $lines = self::findPatternLines($content, $pattern);

        return $lines !== [] ? [$lines[0]] : [];
    }
}
