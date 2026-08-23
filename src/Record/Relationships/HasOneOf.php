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

use Pop\Db\Record;
use Pop\Db\Sql\Parser;

/**
 * Relationship class for "has one of" relationships
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
class HasOneOf extends AbstractRelationship
{

    /**
     * Parent record
     * @var Record
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
     * Get child
     *
     * @return Record
     */
    public function getChild(): Record
    {
        $table = $this->foreignTable;

        $this->assertKeyCardinality($this->foreignKey, (new $table())->getPrimaryKeys());

        $id = is_array($this->foreignKey) ?
            array_map(fn($col) => $this->parent[$col], $this->foreignKey) : $this->parent[$this->foreignKey];

        if (!empty($this->children)) {
            return $table::with($this->children)->getById($id);
        } else {
            return $table::findById($id);
        }
    }

    /**
     * Get the value to use when no eager-loaded result exists for a given leaf record
     *
     * @return mixed
     */
    public function getEmptyRelationshipValue(): mixed
    {
        return null;
    }

    /**
     * Get eager relationships
     *
     * @param  array $ids
     * @throws Exception
     * @return array
     */
    public function getEagerRelationships(array $ids): array
    {
        if (($this->foreignTable === null) || ($this->foreignKey === null)) {
            throw new Exception('Error: The foreign table and key values have not been set.');
        }

        $results = [];
        $table   = $this->foreignTable;
        $db      = $table::db();
        $sql     = $db->createSql();
        $columns = null;

        if (!empty($this->options)) {
            if (isset($this->options['select'])) {
                $columns = $this->options['select'];
            }
        }

        $keys = (new $table())->getPrimaryKeys();
        $this->assertKeyCardinality($this->foreignKey, $keys);

        $sql->select($columns)->from($table::table());

        if (count($keys) > 1) {
            // Wrap all tuple OR-groups in a single AND-nested group, so that anything
            // appended to the WHERE clause afterward is ANDed against the whole
            // "matches any of these id tuples" block rather than becoming a sibling OR
            // at the top level. Renders identically when there is no sibling predicate.
            $tupleGroup = $sql->select()->where->andNest();
            foreach ($ids as $idTuple) {
                $group = $tupleGroup->orNest();
                foreach ($keys as $col) {
                    $group->equalTo($col, $sql->getPlaceholder());
                }
            }
        } else {
            $keys         = reset($keys);
            $placeholders = array_fill(0, count($ids), $sql->getPlaceholder());
            $sql->select()->where->in($keys, $placeholders);
        }

        if (!empty($this->options)) {
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

        $params = is_array($keys) ? array_merge(...$ids) : $ids;

        $db->prepare($sql)
           ->bindParams($params)
           ->execute();

        $rows        = $db->fetchAll();
        $results     = [];
        $leafRecords = [];

        foreach ($rows as $row) {
            $record = new $table();
            $record->setColumns($row);
            $resultKey = is_array($keys) ?
                self::buildCompositeKey(array_map(fn($col) => $row[$col], $keys)) : $row[$keys];
            $results[$resultKey] = $record;
            $leafRecords[] = $record;
        }

        // $keys already holds the foreign table's own primary key columns (a plain string
        // when there is only one), which is what nested child relationships look their
        // leaf records up by — NOT this relationship's foreign key columns, which name
        // columns on the declaring side.
        $this->hydrateChildRelationships($leafRecords, $keys);

        return $results;
    }

}
