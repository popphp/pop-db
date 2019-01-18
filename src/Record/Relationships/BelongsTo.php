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

/**
 * Relationship class for "belongs to" relationships
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.4.0
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
     */
    public function __construct(Record $child, $foreignTable, $foreignKey)
    {
        parent::__construct($foreignTable, $foreignKey);
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

}