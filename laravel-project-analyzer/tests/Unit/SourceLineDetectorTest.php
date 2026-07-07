<?php

use ProjectAnalyzer\Support\SourceLineDetector;

describe('SourceLineDetector', function () {
    it('finds line numbers for pattern matches', function () {
        $content = "<?php\n\neval('test');\n\nDB::raw('select');\n";

        $lines = SourceLineDetector::findPatternLines($content, '/\beval\s*\(/');

        expect($lines)->toBe([3]);
    });

    it('returns multiple matching lines', function () {
        $content = "DB::raw('a');\n\nDB::raw('b');\n";

        $lines = SourceLineDetector::findPatternLines($content, '/DB::raw\s*\(/');

        expect($lines)->toBe([1, 3]);
    });
});
