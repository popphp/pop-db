<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class HasOneOf extends AbstractRelationship
{

    /**
     * Parent record
     * @var Record
     */
    protected $parent = null;

    /**
     * Constructor
     *
     * Instantiate the relationship object
     *
     * @param Record $parent
     * @param string $foreignTable
     * @param string $foreignKey
     * @param array  $options
     */
    public function __construct(Record $parent, $foreignTable, $foreignKey, array $options = null)
    {
        parent::__construct($foreignTable, $foreignKey, $options);
        $this->parent = $parent;
    }

    /**
     * Get parent record
     *
     * @return Record
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Get child
     *
     * @return Record
     */
    public function getChild()
    {
        $table = $this->foreignTable;
        if (!empty($this->children)) {
            return $table::with($this->children)->getById($this->parent[$this->foreignKey]);
        } else {
            return $table::findById($this->parent[$this->foreignKey]);
        }
    }

    /**
     * Get eager relationships
     *
     * @param  array $ids
     * @throws Exception
     * @return array
     */
    public function getEagerRelationships(array $ids)
    {
        if ((null === $this->foreignTable) || (null === $this->foreignKey)) {
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

        if (count($keys) == 1) {
            $keys = $keys[0];
        }
        $placeholders = array_fill(0, count($ids), $sql->getPlaceholder());
        $sql->select($columns)->from($table::table())->where->in($keys, $placeholders);

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
                    $orders = (strpos($this->options['order'], ',') !== false) ?
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
        if (count($primaryKey) == 1) {
            $primaryKey = reset($primaryKey);
        }

        foreach ($rows as $row) {
            $parentIds[] = $row[$primaryKey];
            $record = new $table();
            $record->setColumns($row);
            $results[$row[$keys]] = $record;
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
            if (strpos($children, '.') !== false) {
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