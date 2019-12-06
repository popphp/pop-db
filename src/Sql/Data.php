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
namespace Pop\Db\Sql;

use Pop\Db\Adapter\AbstractAdapter;

/**
 * Data parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
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
     * Conflict key for UPSERT
     * @var string
     */
    protected $conflictKey = null;

    /**
     * Conflict columns for UPSERT
     * @var array
     */
    protected $conflictColumns = [];

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
     * Set what to do on a insert conflict (UPSERT - PostgreSQL & SQLite)
     *
     * @param  array  $columns
     * @param  string $key
     * @return Data
     */
    public function onConflict(array $columns, $key = null)
    {
        $this->conflictColumns = $columns;
        $this->conflictKey     = $key;
        return $this;
    }

    /**
     * Set columns to handle duplicates/conflicts (UPSERT - MySQL-ism)
     *
     * @param  array $columns
     * @return Data
     */
    public function onDuplicateKeyUpdate(array $columns)
    {
        $this->onConflict($columns);
        return $this;
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

        $columns  = array_map([$this, 'quoteId'], $columns);
        $insert   = "INSERT INTO " . $table . " (" . implode(', ', $columns) . ") VALUES" . PHP_EOL;
        $onUpdate = $this->formatConflicts();

        foreach ($data as $i => $row) {
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

            switch ($this->divide) {
                case 0:
                    if ($i == 0) {
                        $this->sql .= $insert;
                    }
                    $this->sql .= $value;
                    $this->sql .= ($i == (count($data) - 1)) ? $onUpdate . ';' : ',';
                    $this->sql .= PHP_EOL;
                    break;
                case 1:
                    $this->sql .= $insert . $value . $onUpdate . ';' . PHP_EOL;
                    break;
                default:
                    if (($i % $this->divide) == 0) {
                        $this->sql .= $insert . $value . (($i == (count($data) - 1)) ? $onUpdate . ';' : ',') . PHP_EOL;
                    } else {
                        $this->sql .= $value;
                        $this->sql .= (((($i + 1) % $this->divide) == 0) || ($i == (count($data) - 1))) ? $onUpdate . ';' : ',';
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
        file_put_contents($to, $header . $this->sql . $footer);
    }

    /**
     * Serialize the data into INSERT statements
     *
     * @param  array   $data
     * @param  string  $to
     * @param  mixed   $omit
     * @param  boolean $nullEmpty
     * @param  string  $header
     * @param  string  $footer
     * @return void
     */
    public function streamToFile(array $data, $to, $omit = null, $nullEmpty = false, $header = null, $footer = null)
    {
        if (!file_exists($to)) {
            touch($to);
        }

        $handle = fopen($to, 'w+');

        if (null !== $header) {
            fwrite($handle, $header);
        }

        if (null !== $omit) {
            $omit = (!is_array($omit)) ? [$omit] : $omit;
        }

        $table    = $this->quoteId($this->table);
        $columns  = array_keys(reset($data));

        if (!empty($omit)) {
            foreach ($omit as $o) {
                if (in_array($o, $columns)) {
                    unset($columns[array_search($o, $columns)]);
                }
            }
        }

        $columns  = array_map([$this, 'quoteId'], $columns);
        $insert   = "INSERT INTO " . $table . " (" . implode(', ', $columns) . ") VALUES" . PHP_EOL;
        $onUpdate = $this->formatConflicts();

        foreach ($data as $i => $row) {
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

            switch ($this->divide) {
                case 0:
                    if ($i == 0) {
                        fwrite($handle, $insert);
                    }
                    fwrite($handle, $value);
                    fwrite($handle, ($i == (count($data) - 1)) ? $onUpdate . ';' : ',');
                    fwrite($handle, PHP_EOL);
                    break;
                case 1:
                    fwrite($handle, $insert . $value . ';' . PHP_EOL);
                    break;
                default:
                    if (($i % $this->divide) == 0) {
                        fwrite($handle, $insert . $value . (($i == (count($data) - 1)) ? $onUpdate . ';' : ',') . PHP_EOL);
                    } else {
                        fwrite($handle, $value);
                        fwrite($handle, ((((($i + 1) % $this->divide) == 0) || ($i == (count($data) - 1))) ? $onUpdate . ';' : ','));
                        fwrite($handle, PHP_EOL);
                    }
            }
        }


        if (null !== $footer) {
            fwrite($handle, $footer);
        }

        fclose($handle);
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

    /**
     * Method to format conflicts (UPSERT)
     *
     * @return string
     */
    protected function formatConflicts()
    {
        $onUpdate = null;

        if (!empty($this->conflictColumns)) {
            $updates = [];
            switch ($this->dbType) {
                case self::MYSQL:
                    foreach ($this->conflictColumns as $conflictColumn) {
                        $updates[] = $this->quoteId($conflictColumn) . ' = VALUES(' . $conflictColumn .')';
                    }
                    $onUpdate = PHP_EOL . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
                    break;
                case self::SQLITE:
                case self::PGSQL:
                    foreach ($this->conflictColumns as $conflictColumn) {
                        $updates[] = $this->quoteId($conflictColumn) . ' = excluded.' . $conflictColumn;
                    }
                    $onUpdate = PHP_EOL . ' ON CONFLICT (' . $this->quoteId($this->conflictKey) . ') DO UPDATE SET '
                        . implode(', ', $updates);
                    break;
            }
        }

        return $onUpdate;
    }

}