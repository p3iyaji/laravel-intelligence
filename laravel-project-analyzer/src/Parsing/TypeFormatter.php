<?php

namespace ProjectAnalyzer\Parsing;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

class TypeFormatter
{
    public static function format(?Node $type): ?string
    {
        if ($type === null) {
            return null;
        }

        if ($type instanceof UnionType) {
            return implode('|', array_map(
                fn (Node $t) => self::format($t),
                $type->types
            ));
        }

        if ($type instanceof IntersectionType) {
            return implode('&', array_map(
                fn (Node $t) => self::format($t),
                $type->types
            ));
        }

        if ($type instanceof NullableType) {
            $inner = self::format($type->type);

            if ($inner === null) {
                return null;
            }

            return str_starts_with($inner, '?') ? $inner : '?'.$inner;
        }

        if ($type instanceof Identifier) {
            return $type->toString();
        }

        if ($type instanceof Name) {
            return $type->toString();
        }

        if (method_exists($type, 'toString')) {
            return $type->toString();
        }

        return null;
    }
}
