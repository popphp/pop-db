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
     * Candidate value to test for containment
     *
     * Deliberately stored OUTSIDE of $this->values: the containment candidate is a raw PHP
     * value that gets json_encode()'d verbatim at render time, never a bound-parameter
     * placeholder token. PredicateSet::addPredicate() walks every element of a predicate's
     * $values array through AbstractSql::isParameter()/getParameter(), which would silently
     * rewrite candidates such as true, '?', '$1' (or arrays containing them) into a dialect
     * placeholder token - corrupting the rendered SQL and, on PostgreSQL, desyncing the
     * parameter-number sequence for unrelated sibling predicates. Keeping it off the values
     * array keeps it out of that loop entirely.
     *
     * @var mixed
     */
    protected mixed $candidate = null;

    /**
     * Flag denoting whether a candidate value was supplied
     * @var bool
     */
    protected bool $hasCandidate = false;

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
        // Split the raw candidate value off into its own property (see $candidate above),
        // leaving only [column, path] - two plain strings - in $this->values.
        if (count($values) == 3) {
            $values             = array_values($values);
            $this->candidate    = $values[2];
            $this->hasCandidate = true;
            $values             = [$values[0], $values[1]];
        }

        parent::__construct($values, $conjunction);
    }

    /**
     * Get the candidate value
     *
     * @return mixed
     */
    public function getCandidate(): mixed
    {
        return $this->candidate;
    }

    /**
     * Determine if a candidate value was supplied
     *
     * @return bool
     */
    public function hasCandidate(): bool
    {
        return $this->hasCandidate;
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
        if (!is_array($this->values) || (count($this->values) != 2) || !$this->hasCandidate) {
            throw new Exception('Error: The values array must have 3 values in it (column, path, value).');
        }

        [$column, $path] = $this->values;
        $encoded         = json_encode($this->candidate);

        if ($encoded === false) {
            throw new Exception('Error: The value for JSON containment could not be encoded as JSON.');
        }

        // Force quoting: a JSON-encoded string value (e.g. '"admin"') starts and ends with the
        // same character PostgreSQL uses to quote identifiers, which would otherwise fool the
        // non-forced quote() heuristic into treating it as "already quoted" and skip wrapping it.
        $candidate = (string)$sql->quote($encoded, true);

        // quote()'s forced branch still leaves an all-digit string unquoted (json_encode(5)
        // === '5'), and PostgreSQL cannot cast a bare integer literal to jsonb. Wrap it so the
        // candidate is always a quoted JSON document literal. Digits need no escaping.
        if (!str_starts_with($candidate, "'")) {
            $candidate = "'" . $candidate . "'";
        }

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
