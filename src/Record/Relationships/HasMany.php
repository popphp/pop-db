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

use Pop\Db\Record;
use Pop\Db\Record\Collection;

/**
 * Relationship class for "has many" relationships
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.4.1
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

}