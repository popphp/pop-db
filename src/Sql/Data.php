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
     * SQL string
     * @var string
     */
    protected $sql = null;

    /**
     * Constructor
     *
     * Instantiate the SQL object
     *
     * @param  AbstractAdapter $db
     * @param  string          $table
     * @param  int             $divide
     */
    public function __construct(AbstractAdapter $db, $table = 'pop_db_data', $divide = 1)
    {
        parent::__construct($db);
        $this->setDivide($divide);
        $this->setTable($table);
    }

    /**
     * Set the INSERT divide
     *
     * @param  int $divide
     * @return Data
     */
    public function setDivide($divide)
    {
        $this->divide = (int)$divide;
        return $this;
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
     * Set the database table
     *
     * @param  string $table
     * @return Data
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
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
     * Get SQL string
     *
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * Check if data was serialized into SQL
     *
     * @return boolean
     */
    public function isSerialized()
    {
        return (null !== $this->sql);
    }

    /**
     * Serialize the data into INSERT statements
     *
     * @param  array   $data
     * @param  mixed   $omit
     * @param  boolean $nullEmpty
     * @return string
     */
    public function serialize(array $data, $omit = null, $nullEmpty = false)
    {
        if (null !== $omit) {
            $omit = (!is_array($omit)) ? [$omit] : $omit;
        }

        $this->sql = '';
        $table     = $this->quoteId($this->table);
        $columns   = array_keys(reset($data));

        if (!empty($omit)) {
            foreach ($omit as $o) {
                if (in_array($o, $columns)) {
                    unset($columns[array_search($o, $columns)]);
                }
            }
        }

        $columns = array_map([$this, 'quoteId'], $columns);
        $insert  = "INSERT INTO " . $table . " (" . implode(', ', $columns) . ") VALUES" . PHP_EOL;
        $values  = [];

        foreach ($data as $row) {
            if (!empty($omit)) {
                foreach ($omit as $o) {
                    if (isset($row[$o])) {
                        unset($row[$o]);
                    }
                }
            }
            $value = "(" . implode(', ', array_map([$this, 'quote'], $row)) . ")";
            if ($nullEmpty) {
                $value = str_replace(["'', ", ", '')"], ['NULL, ', ', NULL)'], $value);
            }
            $values[] = $value;
        }

        switch ($this->divide) {
            case 0:
                $this->sql .= $insert;
                foreach ($values as $i => $value) {
                    $this->sql .= $value;
                    $this->sql .= ($i == (count($values) - 1)) ? ';' : ',';
                    $this->sql .= PHP_EOL;
                }
                break;
            case 1:
                foreach ($values as $i => $value) {
                    $this->sql .= $insert . $value . ';' . PHP_EOL;
                }
                break;
            default:
                foreach ($values as $i => $value) {
                    if (($i % $this->divide) == 0) {
                        $this->sql .= $insert . $value . ',' . PHP_EOL;
                    } else {
                        $this->sql .= $value;
                        $this->sql .= ((($i + 1) % $this->divide) == 0) ? ';' : ',';
                        $this->sql .= PHP_EOL;
                    }
                }
        }

        return $this->sql;
    }

    /**
     * Output SQL to a file
     *
     * @param  string $to
     * @param  string $header
     * @param  string $footer
     * @return void
     */
    public function writeToFile($to, $header = null, $footer = null)
    {
        if (!$this->isSerialized()) {
            throw new Exception('Error: The SQL data has not been serialized yet.');
        }

        file_put_contents($to, $header . $this->sql . $footer);
    }

    /**
     * __toString magic method
     *
     * @return string
     */
    public function __toString()
    {
        return $this->sql;
    }

}