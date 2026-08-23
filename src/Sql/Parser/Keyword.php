<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Parser;

/**
 * Quote-aware keyword scanning class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
class Keyword
{

    /**
     * Find the position of the first occurrence of $needle in $haystack, ignoring any
     * occurrence found inside a single-quoted, double-quoted or backtick-quoted span.
     *
     * @param  string $haystack
     * @param  string $needle
     * @param  int    $offset
     * @param  bool   $caseInsensitive
     * @return int|false
     */
    public static function indexOf(string $haystack, string $needle, int $offset = 0, bool $caseInsensitive = true): int|false
    {
        $length    = strlen($haystack);
        $needleLen = strlen($needle);
        $inQuote   = null;
        $i         = $offset;

        if ($needleLen === 0) {
            return false;
        }

        while ($i <= ($length - $needleLen)) {
            $char = $haystack[$i];

            if ($inQuote !== null) {
                if ($char === $inQuote) {
                    $inQuote = null;
                }
                $i++;
                continue;
            }

            if (($char === "'") || ($char === '"') || ($char === '`')) {
                $inQuote = $char;
                $i++;
                continue;
            }

            $slice = substr($haystack, $i, $needleLen);
            if ($caseInsensitive ? (strcasecmp($slice, $needle) === 0) : ($slice === $needle)) {
                return $i;
            }

            $i++;
        }

        return false;
    }

    /**
     * Split an expression string on AND/OR (case-sensitive, word-boundary-aware),
     * ignoring matches inside a quoted span. Returns the same alternating
     * [expr, 'AND'|'OR', expr, ...] shape as the preg_split call it replaces.
     *
     * @param  string $expression
     * @return array
     */
    public static function split(string $expression): array
    {
        $tokens  = [];
        $current = '';
        $length  = strlen($expression);
        $inQuote = null;
        $i       = 0;

        while ($i < $length) {
            $char = $expression[$i];

            if ($inQuote !== null) {
                $current .= $char;
                if ($char === $inQuote) {
                    $inQuote = null;
                }
                $i++;
                continue;
            }

            if (($char === "'") || ($char === '"') || ($char === '`')) {
                $inQuote  = $char;
                $current .= $char;
                $i++;
                continue;
            }

            $matched = self::matchKeywordAt($expression, $i, ['AND', 'OR']);

            if ($matched !== null) {
                $tokens[] = trim($current);
                $tokens[] = $matched;
                $current  = '';
                $i       += strlen($matched);
            } else {
                $current .= $char;
                $i++;
            }
        }

        if (trim($current) !== '') {
            $tokens[] = trim($current);
        }

        return array_values(array_filter($tokens, fn($token) => $token !== ''));
    }

    /**
     * Determine if one of the given keywords matches exactly (case-sensitive) at the
     * given position, bounded by non-alphanumeric/non-underscore characters on both sides.
     *
     * @param  string $expression
     * @param  int    $position
     * @param  array  $keywords
     * @return ?string
     */
    protected static function matchKeywordAt(string $expression, int $position, array $keywords): ?string
    {
        foreach ($keywords as $keyword) {
            $keywordLen = strlen($keyword);

            if (substr($expression, $position, $keywordLen) !== $keyword) {
                continue;
            }

            $before   = ($position > 0) ? $expression[$position - 1] : ' ';
            $afterPos = $position + $keywordLen;
            $after    = ($afterPos < strlen($expression)) ? $expression[$afterPos] : ' ';

            $beforeIsBoundary = !ctype_alnum($before) && ($before !== '_');
            $afterIsBoundary  = !ctype_alnum($after) && ($after !== '_');

            if ($beforeIsBoundary && $afterIsBoundary) {
                return $keyword;
            }
        }

        return null;
    }

}
