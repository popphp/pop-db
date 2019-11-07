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
     */
    public function __construct(Record $parent, $foreignTable, $foreignKey)
    {
        parent::__construct($foreignTable, $foreignKey);
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
        return $table::findById($this->parent[$this->foreignKey]);
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

        $keys = (new $table())->getPrimaryKeys();

        if (count($keys) == 1) {
            $keys = $keys[0];
        }
        $placeholders = array_fill(0, count($ids), $sql->getPlaceholder());
        $sql->select()->from($table::table())->where->in($keys, $placeholders);

        $db->prepare($sql)
           ->bindParams($ids)
           ->execute();

        $rows = $db->fetchAll();

        foreach ($rows as $row) {
            $record = new $table();
            $record->setColumns($row);
            $results[$row[$keys]] = $record;
        }

        return $results;
    }

}