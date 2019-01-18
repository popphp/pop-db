<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Record\Relationships;

use Pop\Db\Record\Collection;

/**
 * Relationship class for "has one" relationships
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.4.0
 */
abstract class AbstractRelationship implements RelationshipInterface
{

    /**
     * Foreign table class
     * @var string
     */
    protected $foreignTable = null;

    /**
     * Foreign key
     * @var string
     */
    protected $foreignKey = null;

    /**
     * Constructor
     *
     * Instantiate the relationship object
     *
     * @param string $foreignTable
     * @param string $foreignKey
     */
    public function __construct($foreignTable, $foreignKey)
    {
        $this->foreignTable = $foreignTable;
        $this->foreignKey   = $foreignKey;
    }

    /**
     * Get foreign table class
     *
     * @return string
     */
    public function getForeignTable()
    {
        return $this->foreignTable;
    }

    /**
     * Get foreign key
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
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

        $placeholders = array_fill(0, count($ids), $sql->getPlaceholder());
        $sql->select()->from($table::table())->where->in($this->foreignKey, $placeholders);

        $db->prepare($sql)
           ->bindParams($ids)
           ->execute();

        $rows = $db->fetchAll();

        foreach ($rows as $row) {
            if (!isset($results[$row[$this->foreignKey]])) {
                $results[$row[$this->foreignKey]] = [];
            }
            $results[$row[$this->foreignKey]][] = $row;
        }

        return $results;
    }

}