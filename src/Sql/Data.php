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
namespace Pop\Db\Sql;

use Pop\Db\Adapter\AbstractAdapter;

/**
 * Date parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Data extends AbstractSql
{

    /**
     * Database table
     * @var string
     */
    protected $table = 'pop_db_data';

    /**
     * Divide INSERT groups by # (0 creates one big INSERT statement, 1 creates an INSERT statement per row)
     * @var int
     */
    protected $divide = 1;

    /**
     * Constructor
     *
     * Instantiate the SQL object
     *
     * @param  AbstractAdapter $db
     * @param  int             $divide
     * @param  string          $table
     */
    public function __construct(AbstractAdapter $db, $divide = 1, $table = 'pop_db_data')
    {
        parent::__construct($db);
        $this->divide = (int)$divide;
        $this->table  = $table;
    }

    /**
     * Get the INSERT divide
     *
     * @return int
     */
    public function getDivide()
    {
        return $this->divide;
    }

    /**
     * Get the database table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Serialize the data into INSERT statements
     *
     * @param  array $data
     * @return string
     */
    public function serialize(array $data)
    {
        $sql = '';

        $table   = $this->quoteId($this->table);
        $columns = array_map([$this, 'quoteId'], array_keys(reset($data)));
        $insert  = "INSERT INTO " . $table . " (" . implode(', ', $columns) . ") VALUES" . PHP_EOL;
        $values  = [];

        foreach ($data as $row) {
            $values[] = "(" . implode(', ', array_map([$this, 'quote'], $row)) . ")";
        }

        switch ($this->divide) {
            case 0:
                $sql .= $insert;
                foreach ($values as $i => $value) {
                    $sql .= $value;
                    $sql .= ($i == (count($values) - 1)) ? ';' : ',';
                    $sql .= PHP_EOL;
                }
                break;
            case 1:
                foreach ($values as $i => $value) {
                    $sql .= $insert . $value . ';' . PHP_EOL;
                }
                break;
            default:
                foreach ($values as $i => $value) {
                    if (($i % $this->divide) == 0) {
                        $sql .= $insert . $value . ',' . PHP_EOL;
                    } else {
                        $sql .= $value;
                        $sql .= ((($i + 1) % $this->divide) == 0) ? ';' : ',';
                        $sql .= PHP_EOL;
                    }
                }
        }


        return $sql;
    }

}