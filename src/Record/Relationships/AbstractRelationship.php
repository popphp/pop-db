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
namespace Pop\Db\Record\Relationships;

use Pop\Db\Record\Collection;
use Pop\Db\Sql;

/**
 * Relationship class for "has one" relationships
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
abstract class AbstractRelationship implements RelationshipInterface
{

    /**
     * Delimiter used to join multiple column values into one composite lookup key
     * @var string
     */
    public const COMPOSITE_KEY_DELIMITER = "\x1F";

    /**
     * Foreign table class
     * @var ?string
     */
    protected ?string $foreignTable = null;

    /**
     * Foreign key
     * @var string|array|null
     */
    protected string|array|null $foreignKey = null;

    /**
     * Relationship options
     * @var ?array
     */
    protected ?array $options = null;

    /**
     * Relationship children (list of dotted child paths to eager-load under this relationship)
     * @var array
     */
    protected array $children = [];

    /**
     * Constructor
     *
     * Instantiate the relationship object
     *
     * @param string $foreignTable
     * @param string|array $foreignKey
     * @param ?array $options
     */
    public function __construct(string $foreignTable, string|array $foreignKey, ?array $options = null)
    {
        $this->foreignTable = $foreignTable;
        $this->foreignKey   = $foreignKey;
        $this->options      = $options;
    }

    /**
     * Get foreign table class
     *
     * @return string|null
     */
    public function getForeignTable(): string|null
    {
        return $this->foreignTable;
    }

    /**
     * Get foreign key
     *
     * @return string|array|null
     */
    public function getForeignKey(): string|array|null
    {
        return $this->foreignKey;
    }

    /**
     * Get options
     *
     * @return array|null
     */
    public function getOptions(): array|null
    {
        return $this->options;
    }

    /**
     * Get child relationships
     *
     * @return array
     */
    public function getChildRelationships(): array
    {
        return $this->children;
    }

    /**
     * Set children child relationships
     *
     * @param  array $children
     * @return static
     */
    public function setChildRelationships(array $children): static
    {
        $this->children = $children;
        return $this;
    }

    /**
     * Get eager relationships
     *
     * @param  array $ids
     * @throws Exception
     * @return array
     */
    abstract public function getEagerRelationships(array $ids): array;

    /**
     * Get the value to use for a leaf record's named child relationship when no eager-loaded
     * results were found for it (e.g. a "has many" child with zero matching rows should still
     * see an empty Collection rather than a plain array). Concrete relationship classes whose
     * populated results aren't plain arrays should override this.
     *
     * @return mixed
     */
    public function getEmptyRelationshipValue(): mixed
    {
        return [];
    }

    /**
     * Build a single composite lookup key from an ordered list of column values
     *
     * @param  array $values
     * @return string
     */
    public static function buildCompositeKey(array $values): string
    {
        return implode(self::COMPOSITE_KEY_DELIMITER, $values);
    }

    /**
     * Build an ordered tuple of values for the given columns from a record (either a
     * Record instance or a plain array row). Returns null if any one of the columns is
     * missing or null, i.e. the record has no usable composite key and should be skipped.
     *
     * @param  mixed $record
     * @param  array $columns
     * @return array|null
     */
    public static function tupleFor(mixed $record, array $columns): ?array
    {
        $tuple = array_map(fn($col) => $record[$col] ?? null, $columns);
        return (in_array(null, $tuple, true)) ? null : $tuple;
    }

    /**
     * Bind a single value as a query parameter and return the placeholder token that must be
     * rendered in its place.
     *
     * A bare placeholder character is NOT a usable placeholder on every dialect: only MySQL/SQL
     * Server take a bare '?'. PostgreSQL needs a positional '$N' and SQLite/PDO need a named
     * ':name', so the token has to be generated here rather than repeating whatever
     * Sql::getPlaceholder() returns. The Sql object's own parameter counter is used to number
     * (PostgreSQL) and to uniquely name (SQLite/PDO) every parameter in the statement, so
     * callers can bind several groups of parameters into one query without colliding.
     *
     * The generated names deliberately start with the counter, i.e. with a digit, which is
     * exactly what keeps them from ever colliding with the column-derived names that
     * Sql\Parser\Expression::parseShorthand() emits for the same statement.
     *
     * @param  Sql    $sql
     * @param  string $column
     * @param  mixed  $value
     * @param  array  $params
     * @return string
     */
    protected static function bindPlaceholder(Sql $sql, string $column, mixed $value, array &$params): string
    {
        $placeholder = $sql->getPlaceholder();

        $sql->incrementParameterCount();

        if ($placeholder == ':') {
            $key          = $sql->getParameterCount() . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $column);
            $params[$key] = $value;
            return ':' . $key;
        } else if ($placeholder == '$') {
            $params[] = $value;
            return '$' . $sql->getParameterCount();
        } else {
            $params[] = $value;
            return '?';
        }
    }

    /**
     * Bind a flat list of values for a single column and return their placeholder tokens
     *
     * @param  Sql    $sql
     * @param  string $column
     * @param  array  $values
     * @param  array  $params
     * @return array
     */
    protected static function bindPlaceholders(Sql $sql, string $column, array $values, array &$params): array
    {
        $placeholders = [];

        foreach ($values as $value) {
            $placeholders[] = static::bindPlaceholder($sql, $column, $value, $params);
        }

        return $placeholders;
    }

    /**
     * Apply the eager-load id filter to a SELECT's WHERE clause and collect its bound values.
     *
     * A single-column key renders as one "column IN (...)" predicate. A composite key renders
     * as one AND-nested group of per-tuple OR-nested equality groups, so that whatever gets
     * appended to the WHERE clause afterward is ANDed against the whole "matches any of these
     * id tuples" block rather than becoming a sibling OR at the top level. It renders
     * identically when there is no sibling predicate.
     *
     * @param  Sql          $sql
     * @param  string|array $key
     * @param  array        $ids
     * @param  array        $params
     * @return void
     */
    protected function applyEagerIdFilter(Sql $sql, string|array $key, array $ids, array &$params): void
    {
        if (is_array($key)) {
            $columns    = array_values($key);
            $tupleGroup = $sql->select()->where->andNest();
            foreach ($ids as $idTuple) {
                $idTuple = array_values((array)$idTuple);
                $group   = $tupleGroup->orNest();
                foreach ($columns as $i => $column) {
                    $group->equalTo($column, static::bindPlaceholder($sql, $column, $idTuple[$i] ?? null, $params));
                }
            }
        } else {
            $sql->select()->where->in($key, static::bindPlaceholders($sql, $key, $ids, $params));
        }
    }

    /**
     * Validate that the tuples in an eager-load id list have the same number of
     * components as the array foreign key they will be bound to. A plain string
     * $foreignKey (cardinality 1) and an empty $ids list are always valid.
     *
     * @param  array        $ids
     * @param  string|array $foreignKey
     * @throws Exception
     * @return void
     */
    protected function assertTupleCardinality(array $ids, string|array $foreignKey): void
    {
        if (!is_array($foreignKey) || empty($ids)) {
            return;
        }

        $tuple = reset($ids);

        if (!is_array($tuple) || (count($tuple) !== count($foreignKey))) {
            throw new Exception(
                'Error: The number of foreign key columns (' . count($foreignKey) .
                ') does not match the number of values (' . (is_array($tuple) ? count($tuple) : 1) .
                ') provided for the relationship lookup.'
            );
        }
    }

    /**
     * Validate that an array foreign-key column count matches the target
     * table's own primary-key column count. A plain string $foreignKey is
     * always treated as cardinality 1.
     *
     * @param  string|array $foreignKey
     * @param  array        $targetPrimaryKeys
     * @throws Exception
     * @return void
     */
    protected function assertKeyCardinality(string|array $foreignKey, array $targetPrimaryKeys): void
    {
        $foreignKeyCount = is_array($foreignKey) ? count($foreignKey) : 1;
        $targetKeyCount  = count($targetPrimaryKeys);

        if ($foreignKeyCount !== $targetKeyCount) {
            throw new Exception(
                'Error: The number of foreign key columns (' . $foreignKeyCount .
                ') does not match the number of primary key columns (' . $targetKeyCount .
                ') on the target table.'
            );
        }
    }

    /**
     * Determine whether a lazily-built relationship lookup's column filter carries at least
     * one usable parent key value, i.e. the parent record was actually loaded.
     *
     * An unloaded parent degenerates the lookup two different ways depending on whether the
     * foreign key is a single column or composite: the single-key branch collapses
     * getPrimaryValues()'s empty array into a bare `[]` value (legacy empty-IN shorthand,
     * `IN ('')`), while the composite branch reads each column through the row gateway and
     * gets `null` for each one (`IS NULL`, which matches any orphan row with a null FK). Both
     * mean "no parent loaded" and must short-circuit before either shape reaches the query
     * layer - but a legitimate `0` or `''` key value must NOT be treated the same way, so this
     * checks value identity, not truthiness.
     *
     * @param  array $columns
     * @return bool
     */
    protected function hasUsableParentKey(array $columns): bool
    {
        return !empty(array_filter($columns, fn($value) => ($value !== null) && ($value !== [])));
    }

    /**
     * Hydrate nested child relationships onto a flat list of leaf records, resolving each
     * named child relationship once (accumulated by name) and distributing every one of them
     * onto every leaf record — so multiple differently-named children under this relationship
     * don't overwrite each other.
     *
     * The column used to query and match a given child relationship is decided per relationship
     * name, not once for all of them: "to-one by foreign key" children (HasOneOf and BelongsTo)
     * are keyed by the leaf record's own foreign key column — the column holding the value that
     * identifies which foreign row to fetch — while every other kind is keyed by the leaf
     * record's primary key.
     *
     * @param  array        $leafRecords
     * @param  string|array $primaryKeyColumn
     * @return void
     */
    protected function hydrateChildRelationships(array $leafRecords, string|array $primaryKeyColumn): void
    {
        if (empty($this->children) || empty($leafRecords)) {
            return;
        }

        $accumulated         = [];
        $relationshipsByName = [];
        $lookupColumns       = [];

        foreach ($leafRecords as $record) {
            foreach ($this->children as $childPath) {
                $record->addWith($childPath);
            }
            $record->getWithRelationships();
            foreach ($record->getRelationships() as $name => $relationship) {
                if (!isset($accumulated[$name])) {
                    $column = (($relationship instanceof HasOneOf) || ($relationship instanceof BelongsTo)) ?
                        $relationship->getForeignKey() : $primaryKeyColumn;

                    $lookupColumns[$name] = $column;

                    if (is_array($column)) {
                        $tuplesByKey = [];
                        foreach ($leafRecords as $leafRecord) {
                            $tuple = self::tupleFor($leafRecord, $column);
                            if ($tuple === null) {
                                continue;
                            }
                            $tuplesByKey[self::buildCompositeKey($tuple)] = $tuple;
                        }
                        $ids = array_values($tuplesByKey);
                    } else {
                        $ids = array_values(array_unique(array_map(
                            fn($leafRecord) => $leafRecord[$column], $leafRecords
                        )));
                    }

                    // An empty id list must not reach getEagerRelationships(), which would
                    // render either an invalid or an entirely unfiltered WHERE clause.
                    $accumulated[$name]         = (!empty($ids)) ? $relationship->getEagerRelationships($ids) : [];
                    $relationshipsByName[$name] = $relationship;
                }
            }
        }

        foreach ($leafRecords as $record) {
            foreach ($accumulated as $name => $resultsByKey) {
                $column = $lookupColumns[$name];
                if (is_array($column)) {
                    $tuple       = self::tupleFor($record, $column);
                    $lookupValue = ($tuple !== null) ? self::buildCompositeKey($tuple) : null;
                } else {
                    $lookupValue = $record[$column] ?? null;
                }
                $record->setRelationship(
                    $name,
                    $resultsByKey[$lookupValue] ?? $relationshipsByName[$name]->getEmptyRelationshipValue()
                );
            }
        }
    }

}
