<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Predicate;

use Pop\Db\Sql\AbstractSql;
use Pop\Db\Sql\JsonExtract;

/**
 * Json Contains predicate class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
class JsonContains extends AbstractPredicate
{

    /**
     * Constructor
     *
     * Instantiate the JSON CONTAINS predicate set object
     *
     * @param  array  $values
     * @param  string $conjunction
     * @throws Exception
     */
    public function __construct(array $values, string $conjunction = 'AND')
    {
        parent::__construct($values, $conjunction);
    }

    /**
     * Render the predicate string
     *
     * @param  AbstractSql $sql
     * @throws Exception
     * @return string
     */
    public function render(AbstractSql $sql): string
    {
        if (count($this->values) != 3) {
            throw new Exception('Error: The values array must have 3 values in it (column, path, value).');
        }

        [$column, $path, $value] = $this->values;

        // Force quoting: a JSON-encoded string value (e.g. '"admin"') starts and ends with the
        // same character PostgreSQL uses to quote identifiers, which would otherwise fool the
        // non-forced quote() heuristic into treating it as "already quoted" and skip wrapping it.
        $candidate = $sql->quote(json_encode($value), true);

        if ($sql->isMysql()) {
            $rendered = 'JSON_CONTAINS(' . $sql->quoteId($column) . ', ' . $candidate . ', ' . $sql->quote($path) . ')';
        } else if ($sql->isPgsql()) {
            $segments = JsonExtract::parsePathSegments($path);
            $pgPath   = '{' . implode(',', $segments) . '}';
            $rendered = '(' . $sql->quoteId($column) . ' #> ' . $sql->quote($pgPath) . ') @> ' . $candidate . '::jsonb';
        } else {
            throw new Exception('Error: JSON containment is not supported on this database type.');
        }

        return '(' . $rendered . ')';
    }

}
