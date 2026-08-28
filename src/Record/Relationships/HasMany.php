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

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Record;
use Pop\Db\Sql;
use Pop\Db\Sql\Parser;

/**
 * Relationship class for "has many" relationships
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
class HasMany extends AbstractRelationship
{

    /**
     * Parent record
     * @var ?Record
     */
    protected ?Record $parent = null;

    /**
     * Constructor
     *
     * Instantiate the relationship object
     *
     * @param Record $parent
     * @param string $foreignTable
     * @param string|array $foreignKey
     * @param ?array $options
     */
    public function __construct(Record $parent, string $foreignTable, string|array $foreignKey, ?array $options = null)
    {
        parent::__construct($foreignTable, $foreignKey, $options);
        $this->parent = $parent;
    }

    /**
     * Get parent record
     *
     * @return ?Record
     */
    public function getParent(): ?Record
    {
        return $this->parent;
    }

    /**
     * Get children
     *
     * @param  ?array $options
     * @return Record\Collection
     */
    public function getChildren(?array $options = null): Record\Collection
    {
        $table = $this->foreignTable;

        if (is_array($this->foreignKey)) {
            $parentPrimaryKeys = $this->parent->getPrimaryKeys();
            $this->assertKeyCardinality($this->foreignKey, $parentPrimaryKeys);
            $columns = [];
            foreach ($this->foreignKey as $i => $fkColumn) {
                $columns[$fkColumn] = $this->parent[$parentPrimaryKeys[$i]];
            }
        } else {
            $values = array_values($this->parent->getPrimaryValues());

            if (count($values) == 1) {
                $values = $values[0];
            }

            $columns = [$this->foreignKey => $values];
        }

        // An unloaded parent (e.g. Table::findById($missingId)) has no usable primary key
        // value to look children up by - return the same empty result a real parent with no
        // children would, without asking the database a degenerate question (RELATIONSHIP-
        // GUARD-HANDOFF.md §1/§2).
        if (!$this->hasUsableParentKey($columns)) {
            return new Record\Collection();
        }

        if (!empty($options) && !empty($options['columns'])) {
            $columns = array_merge($columns, $options['columns']);
        }

        if (!empty($this->children)) {
            return $table::with($this->children)->getBy($columns, $options);
        } else {
            return $table::findBy($columns, $options);
        }
    }

    /**
     * Get the value to use when no eager-loaded result exists for a given leaf record
     *
     * @return mixed
     */
    public function getEmptyRelationshipValue(): mixed
    {
        return new Record\Collection();
    }

    /**
     * Get eager relationships
     *
     * @param  array $ids
     * @param  bool  $toArray
     * @throws Exception
     * @return array
     */
    public function getEagerRelationships(array $ids, bool|array $toArray = false): array
    {
        if (($this->foreignTable === null) || ($this->foreignKey === null)) {
            throw new Exception('Error: The foreign table and key values have not been set.');
        }

        // The foreign key columns on the foreign table mirror the declaring (parent)
        // table's own primary key columns, so their counts must match — the same
        // invariant the lazy getChildren() path asserts.
        if (is_array($this->foreignKey)) {
            $this->assertKeyCardinality($this->foreignKey, $this->parent->getPrimaryKeys());
            $this->assertTupleCardinality($ids, $this->foreignKey);
        }

        $table   = $this->foreignTable;
        $db      = $table::db();
        $sql     = $db->createSql();
        $columns = null;

        if (!empty($this->options) && isset($this->options['select'])) {
            $columns = $this->options['select'];
        }

        $sql->select($columns)->from($table::table());

        $params = [];

        // The options['columns'] filter is applied BEFORE the id filter so that the parameters
        // are generated (and therefore numbered/named/ordered) in the same order the WHERE
        // clause renders them: PredicateSet::render() always renders its top-level predicates
        // (this filter) before its nested predicate sets (the composite tuple group), and for a
        // single-column key both are top-level predicates rendered in insertion order.
        $this->applyAdditionalColumnsFilter($sql, $params);
        $this->applyEagerIdFilter($sql, $this->foreignKey, $ids, $params);
        $this->applyQueryOptions($sql, $db);

        $db->prepare($sql)
            ->bindParams($params)
            ->execute();

        return $this->hydrateRows($db->fetchAll(), $table, $toArray);
    }

    /**
     * Apply the options['columns'] additional WHERE filter (if any) and collect its bound values
     *
     * The parsed expressions carry their own dialect-correct placeholder tokens, so the SQL
     * object's parameter counter is advanced by one per bound value to keep any placeholder
     * generated afterward for the same statement in step with them.
     *
     * @param  Sql   $sql
     * @param  array $params
     * @return void
     */
    private function applyAdditionalColumnsFilter(Sql $sql, array &$params): void
    {
        if (empty($this->options) || !isset($this->options['columns'])) {
            return;
        }

        $additionalColumns = Parser\Expression::parseShorthand($this->options['columns'], $sql->getPlaceholder());

        if (!empty($additionalColumns['expressions'])) {
            foreach ($additionalColumns['expressions'] as $expression) {
                $sql->select()->where($expression);
            }
        }

        foreach ($additionalColumns['params'] as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            } else {
                $params[] = $value;
            }
            $sql->incrementParameterCount();
        }
    }

    /**
     * Apply the limit, offset, join, and order options (if any)
     *
     * @param  Sql             $sql
     * @param  AbstractAdapter $db
     * @return void
     */
    private function applyQueryOptions(Sql $sql, AbstractAdapter $db): void
    {
        if (empty($this->options)) {
            return;
        }

        if (isset($this->options['limit'])) {
            $sql->select()->limit((int)$this->options['limit']);
        }

        if (isset($this->options['offset'])) {
            $sql->select()->offset((int)$this->options['offset']);
        }

        if (isset($this->options['join'])) {
            $joins = (is_array($this->options['join']) && isset($this->options['join']['table'])) ?
                [$this->options['join']] : $this->options['join'];

            foreach ($joins as $join) {
                if (isset($join['type']) && method_exists($sql->select(), $join['type'])) {
                    $joinMethod = $join['type'];
                    $sql->select()->{$joinMethod}($join['table'], $join['columns']);
                } else {
                    $sql->select()->leftJoin($join['table'], $join['columns']);
                }
            }
        }

        if (isset($this->options['order'])) {
            if (!is_array($this->options['order'])) {
                $orders = (str_contains($this->options['order'], ',')) ?
                    explode(',', $this->options['order']) : [$this->options['order']];
            } else {
                $orders = $this->options['order'];
            }
            foreach ($orders as $order) {
                $ord = Parser\Order::parse(trim($order));
                $sql->select()->orderBy($ord['by'], $db->escape($ord['order']));
            }
        }
    }

    /**
     * Hydrate the fetched rows into a per-parent-key map of Collections (or raw arrays), and
     * eager-load any nested child relationships on the resulting leaf records
     *
     * @param  array      $rows
     * @param  string     $table
     * @param  bool|array $toArray
     * @return array
     */
    private function hydrateRows(array $rows, string $table, bool|array $toArray): array
    {
        $results     = [];
        $leafRecords = [];

        // The leaf records are rows of the foreign table, so their own primary key
        // columns (NOT this relationship's foreign key columns, which name columns
        // on the declaring side) are what nested child relationships look them up by.
        $primaryKey = (new $table())->getPrimaryKeys();
        $primaryKey = (count($primaryKey) == 1) ? reset($primaryKey) : $primaryKey;

        foreach ($rows as $row) {
            $key = is_array($this->foreignKey) ?
                self::buildCompositeKey(array_map(fn($col) => $row[$col], $this->foreignKey)) :
                $row[$this->foreignKey];

            if ($toArray === false) {
                if (!isset($results[$key])) {
                    $results[$key] = new Record\Collection();
                }
                $record = new $table();
                $record->setColumns($row);
                $results[$key]->push($record);
                $leafRecords[] = $record;
            } else {
                if (!isset($results[$key])) {
                    $results[$key] = [];
                }
                $results[$key][] = $row;
            }
        }

        $this->hydrateChildRelationships($leafRecords, $primaryKey);

        return $results;
    }

}
