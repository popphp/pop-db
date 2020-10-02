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
 * Relationship class for "belongs to" relationships
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class BelongsTo extends AbstractRelationship
{

    /**
     * Child record
     * @var Record
     */
    protected $child;

    /**
     * Constructor
     *
     * Instantiate the relationship object
     *
     * @param Record $child
     * @param string $foreignTable
     * @param string $foreignKey
     * @param array  $options
     */
    public function __construct(Record $child, $foreignTable, $foreignKey, array $options = [])
    {
        parent::__construct($foreignTable, $foreignKey, $options);
        $this->child = $child;
    }

    /**
     * Get child record
     *
     * @return Record
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * Get parent
     *
     * @param  array  $options
     * @return Record
     */
    public function getParent(array $options = null)
    {
        $table  = $this->foreignTable;
        $values = $this->child[$this->foreignKey];

        return $table::findById($values);
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

        $rows = $db->fetchAll();

        foreach ($rows as $row) {
            $record = new $table();
            $record->setColumns($row);
            $results[$row[$this->foreignKey]] = $record;
        }

        return $results;
    }

}