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
namespace Pop\Db\Record\Relationships;

use Pop\Db\Record;
use Pop\Db\Sql\Parser;

/**
 * Relationship class for "has many" relationships
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.8.0
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

        $results = [];
        $table   = $this->foreignTable;
        $db      = $table::db();
        $sql     = $db->createSql();
        $columns = null;

        if (!empty($this->options) && isset($this->options['select'])) {
            $columns = $this->options['select'];
        }

        $sql->select($columns)->from($table::table());

        if (is_array($this->foreignKey)) {
            foreach ($ids as $idTuple) {
                $group = $sql->select()->where->orNest();
                foreach ($this->foreignKey as $fkColumn) {
                    $group->equalTo($fkColumn, $sql->getPlaceholder());
                }
            }
            $params = array_merge(...$ids);
        } else {
            $placeholders = array_fill(0, count($ids), $sql->getPlaceholder());
            $sql->select()->where->in($this->foreignKey, $placeholders);
            $params = $ids;
        }

        if (!empty($this->options) && isset($this->options['columns'])) {
            $additionalColumns = Parser\Expression::parseShorthand($this->options['columns'], $sql->getPlaceholder());
            if (!empty($additionalColumns['expressions'])) {
                foreach ($additionalColumns['expressions'] as $expression) {
                    $sql->select()->where($expression);
                }
            }
            if (!empty($additionalColumns['params'])) {
                $params = array_merge($params, $additionalColumns['params']);
            }
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

        $db->prepare($sql)
            ->bindParams($params)
            ->execute();

        $rows        = $db->fetchAll();
        $results     = [];
        $leafRecords = [];

        $primaryKey = (new $table())->getPrimaryKeys();
        $primaryKey = (count($primaryKey) == 1) ? reset($primaryKey) : $this->foreignKey;

        foreach ($rows as $row) {
            $key = is_array($this->foreignKey) ?
                $this->buildCompositeKey(array_map(fn($col) => $row[$col], $this->foreignKey)) :
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
