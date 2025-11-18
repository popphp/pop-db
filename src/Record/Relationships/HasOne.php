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
 * Relationship class for "has one" relationships
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.8.0
 */
class HasOne extends AbstractRelationship
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
     * @param string $foreignKey
     * @param ?array $options
     */
    public function __construct(Record $parent, string $foreignTable, string $foreignKey, ?array $options = null)
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
     * @param  ?array $options
     * @return Record
     */
    public function getChild(?array $options = null): Record
    {
        $table  = $this->foreignTable;
        $values = array_values($this->parent->getPrimaryValues());

        if (count($values) == 1) {
            $values = $values[0];
        }

        $columns = [$this->foreignKey => $values];

        if (!empty($options) && !empty($options['columns'])) {
            $columns = array_merge($columns, $options['columns']);
        }

        if (!empty($this->children)) {
            return $table::with($this->children)->getOne($columns, $options);
        } else {
            return $table::findOne($columns, $options);
        }
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

        $placeholders = array_fill(0, count($ids), $sql->getPlaceholder());
        $sql->select($columns)->from($table::table())->where->in($this->foreignKey, $placeholders);

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
            ->bindParams($ids)
            ->execute();

        $rows               = $db->fetchAll();
        $parentIds          = [];
        $childRelationships = [];

        $primaryKey = (new $table())->getPrimaryKeys();
        $primaryKey = (count($primaryKey) == 1) ? reset($primaryKey) : $this->foreignKey;

        foreach ($rows as $row) {
            $parentIds[] = $row[$primaryKey];
            $record = new $table();
            $record->setColumns($row);
            $results[$row[$this->foreignKey]] = $record;
        }

        if (!empty($this->children) && !empty($parentIds)) {
            foreach ($results as $record) {
                $record->getWithRelationships();
                foreach ($record->getRelationships() as $relationship) {
                    $childRelationships = $relationship->getEagerRelationships($parentIds);
                }
            }
        }

        if (!empty($childRelationships)) {
            $children    = $this->children;
            $subChildren = null;
            if (str_contains($children, '.')) {
                $names       = explode('.', $children);
                $children    = array_shift($names);
                $subChildren = implode('.', $names);
            }

            foreach ($results as $record) {
                if (!empty($subChildren)) {
                    $record->addWith($subChildren);
                }
                $rel = (isset($childRelationships[$record[$primaryKey]])) ?
                    $childRelationships[$record[$primaryKey]] : [];

                $record->setRelationship($children, $rel);
            }
        }

        return $results;
    }

}
