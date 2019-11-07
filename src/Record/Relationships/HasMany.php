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
 * Relationship class for "has many" relationships
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class HasMany extends AbstractRelationship
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
     * Get children
     *
     * @param  array  $options
     * @return Collection
     */
    public function getChildren(array $options = null)
    {
        $table  = $this->foreignTable;
        $values = array_values($this->parent->getPrimaryValues());

        if (count($values) == 1) {
            $values = $values[0];
        }

        return $table::findBy([$this->foreignKey => $values], $options);
    }

    /**
     * Get eager relationships
     *
     * @param  array   $ids
     * @param  boolean $asArray
     * @throws Exception
     * @return array
     */
    public function getEagerRelationships(array $ids, $asArray = false)
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
            if (!$asArray) {
                if (!isset($results[$row[$this->foreignKey]])) {
                    $results[$row[$this->foreignKey]] = new Record\Collection();
                }
                $record = new $table();
                $record->setColumns($row);
                $results[$row[$this->foreignKey]]->push($record);
            } else {
                if (!isset($results[$row[$this->foreignKey]])) {
                    $results[$row[$this->foreignKey]] = [];
                }
                $results[$row[$this->foreignKey]][] = $row;
            }
        }

        return $results;
    }

}