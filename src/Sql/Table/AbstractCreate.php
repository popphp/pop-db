<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Table;


/**
 * Schema CREATE table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
abstract class AbstractCreate extends AbstractTable
{

    protected $columns           = [];
    protected $indices           = [];
    protected $constraints       = [];
    protected $currentColumn     = null;
    protected $currentConstraint = null;

    public function column($name)
    {
        $this->currentColumn = $name;
        return $this;
    }

    public function constraint($name)
    {
        $this->currentConstraint = $name;
        return $this;
    }

    public function addColumn($name, $type, $size = null, $point = null)
    {
        $this->currentColumn  = $name;
        $this->columns[$name] = [
            'type'      => $type,
            'size'      => $size,
            'point'     => $point,
            'nullable'  => null,
            'default'   => null,
            'increment' => false,
            'unsigned'  => false
        ];

        return $this;
    }

    public function increment()
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['increment'] = true;
        }

        return $this;
    }

    public function default($value)
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['default'] = $value;
        }

        return $this;
    }

    public function nullable()
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['nullable'] = true;
        }

        return $this;
    }

    public function isNotNullable()
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['nullable'] = false;
        }

        return $this;
    }

    public function unsigned()
    {
        if (null !== $this->currentColumn) {
            $this->columns[$this->currentColumn]['unsigned'] = true;
        }

        return $this;
    }

    public function index($column, $name = null, $type = 'index')
    {
        if (null === $name) {
            $name = 'index';
            if (is_array($column)) {
                foreach ($column as $c) {
                    $name .= '_' . strtolower($c);
                }
            } else {
                $name .= '_' . strtolower($column);
            }
        }
        $this->indices[$name] = [
            'column' => $column,
            'type'   => $type
        ];

        return $this;
    }

    public function unique($column, $name = null)
    {
        return $this->index($column, $name, 'unique');
    }

    public function primary($column, $name = null)
    {
        return $this->index($column, $name, 'primary');
    }

    public function foreignKey($column, $name = null)
    {
        if (null === $name) {
            $name = 'fk_'. strtolower($column);
        }
        $this->currentConstraint  = $name;
        $this->constraints[$name] = [
            'references' => null,
            'on'         => null,
            'delete'     => null
        ];
        return $this;
    }

    public function references($foreignTable)
    {
        if (null !== $this->currentConstraint) {
            $this->constraints[$this->currentConstraint]['reference'] = $foreignTable;
        }
    }

    public function on($foreignColumn)
    {
        if (null !== $this->currentConstraint) {
            $this->constraints[$this->currentConstraint]['on'] = $foreignColumn;
        }
    }

    public function onDelete($action = null)
    {
        if (null !== $this->currentConstraint) {
            $this->constraints[$this->currentConstraint]['delete'] = (strtolower($action) == 'cascade') ? 'cascade' : null;
        }
    }

    /*
     * NUMERIC TYPES
     */

    public function integer($name, $size = null)
    {
        $this->addColumn($name, 'integer', $size);
    }

    public function serial($name, $size = null)
    {
        $this->addColumn($name, 'serial', $size);
    }

    public function bigSerial($name, $size = null)
    {
        $this->addColumn($name, 'bigserial', $size);
    }

    public function smallSerial($name, $size = null)
    {
        $this->addColumn($name, 'smallserial', $size);
    }

    public function int($name, $size = null)
    {
        $this->addColumn($name, 'int', $size);
    }

    public function bigInt($name, $size = null)
    {
        $this->addColumn($name, 'bigint', $size);
    }

    public function mediumInt($name, $size = null)
    {
        $this->addColumn($name, 'mediumint', $size);
    }

    public function smallInt($name, $size = null)
    {
        $this->addColumn($name, 'smallint', $size);
    }

    public function tinyInt($name, $size = null)
    {
        $this->addColumn($name, 'tinyint', $size);
    }

    public function float($name, $size = null, $point = null)
    {
        $this->addColumn($name, 'float', $size, $point);
    }

    public function real($name)
    {
        $this->addColumn($name, 'real');
    }

    public function double($name, $size = null, $point = null)
    {
        $this->addColumn($name, 'double', $size, $point);
    }

    public function decimal($name, $size = null, $point = null)
    {
        $this->addColumn($name, 'decimal', $size, $point);
    }

    public function numeric($name, $size = null, $point = null)
    {
        $this->addColumn($name, 'numeric', $size, $point);
    }

    /*
     * DATE & TIME TYPES
     */

    public function date($name)
    {
        $this->addColumn($name, 'date');
    }

    public function time($name)
    {
        $this->addColumn($name, 'time');
    }

    public function datetime($name)
    {
        $this->addColumn($name, 'datetime');
    }

    public function timestamp($name)
    {
        $this->addColumn($name, 'timestamp');
    }

    public function year($name, $size = null)
    {
        $this->addColumn($name, 'year', $size);
    }

    /*
     * CHARACTER TYPES
     */

    public function text($name)
    {
        $this->addColumn($name, 'text');
    }

    public function tinyText($name)
    {
        $this->addColumn($name, 'tinytext');
    }

    public function mediumText($name)
    {
        $this->addColumn($name, 'mediumtext');
    }

    public function longText($name)
    {
        $this->addColumn($name, 'longtext');
    }

    public function blob($name)
    {
        $this->addColumn($name, 'blob');
    }

    public function mediumBlob($name)
    {
        $this->addColumn($name, 'mediumblob');
    }

    public function longBlob($name)
    {
        $this->addColumn($name, 'longblob');
    }

    public function char($name, $size = null)
    {
        $this->addColumn($name, 'char', $size);
    }

    public function varchar($name, $size = null)
    {
        $this->addColumn($name, 'varchar', $size);
    }

}