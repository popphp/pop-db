<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql;

/**
 * Json Extract expression class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
class JsonExtract
{

    /**
     * The rendered, dialect-specific extraction expression
     * @var string
     */
    protected string $rendered;

    /**
     * Constructor
     *
     * Instantiate the JSON extract expression object. Renders immediately - this object is
     * immutable once built, unlike Select, whose state can still change after construction.
     *
     * @param  AbstractSql $sql
     * @param  string      $column
     * @param  string      $path
     * @throws Exception
     */
    public function __construct(AbstractSql $sql, string $column, string $path)
    {
        if ($sql->isMysql()) {
            $this->rendered = 'JSON_UNQUOTE(JSON_EXTRACT(' . $sql->quoteId($column) . ', ' . $sql->quote($path) . '))';
        } else if ($sql->isPgsql()) {
            $segments = self::parsePathSegments($path);
            if (count($segments) <= 1) {
                $key = (count($segments) === 1) ? $segments[0] : '';
                $this->rendered = $sql->quoteId($column) . '->>' . $sql->quote($key);
            } else {
                $this->rendered = $sql->quoteId($column) . '#>>' . $sql->quote('{' . implode(',', $segments) . '}');
            }
        } else if ($sql->isSqlite()) {
            $this->rendered = 'json_extract(' . $sql->quoteId($column) . ', ' . $sql->quote($path) . ')';
        } else if ($sql->isSqlsrv()) {
            $this->rendered = 'JSON_VALUE(' . $sql->quoteId($column) . ', ' . $sql->quote($path) . ')';
        } else {
            throw new Exception('Error: Unsupported database type for JSON extraction.');
        }
    }

    /**
     * Parse a MySQL-style JSONPath string ('$.foo.bar', '$.tags[0]') into an ordered list of
     * path segments ('foo', 'bar' / 'tags', '0'), for dialects (PostgreSQL) that address by
     * segment array rather than JSONPath syntax directly
     *
     * @param  string $path
     * @return array
     */
    public static function parsePathSegments(string $path): array
    {
        $path = ltrim($path, '$');
        $path = preg_replace('/\[(\d+)\]/', '.$1', $path);
        $path = trim($path, '.');

        return ($path === '') ? [] : explode('.', $path);
    }

    /**
     * Render the expression string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->rendered;
    }

}
