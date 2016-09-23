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
namespace Pop\Db\Record;

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Parser;
use Pop\Db\Sql;

/**
 * Result class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Collection extends AbstractRecord
{

    /**
     * Result rows
     * @var array
     */
    protected $rows = [];

    /**
     * Find records by method
     *
     * @param  array  $columns
     * @param  array  $options
     * @param  string $resultsAs
     * @return Collection
     */
    public function findBy(array $columns = null, array $options = null, $resultsAs = 'AS_RESULT')
    {
        $params = null;
        $where  = null;

        if (null !== $columns) {
            $parsedColumns = Parser\Column::parse($columns, $this->sql->getPlaceholder());
            $params        = $parsedColumns['params'];
            $where         = $parsedColumns['where'];
        }

        $this->setRows($this->tableGateway->select(null, $where, $params, $options), $resultsAs);

        return $this;
    }

    /**
     * Find all records method
     *
     * @param  array  $options
     * @param  string $resultsAs
     * @return Collection
     */
    public function findAll(array $options = null, $resultsAs = 'AS_RESULT')
    {
        return $this->findBy(null, $options, $resultsAs);
    }

    /**
     * Method to execute a custom prepared SQL statement.
     *
     * @param  mixed  $sql
     * @param  mixed  $params
     * @param  string $resultsAs
     * @return Collection
     */
    public function execute($sql, $params, $resultsAs = 'AS_RESULT')
    {
        if ($sql instanceof Sql) {
            $sql = (string)$sql;
        }
        if (!is_array($params)) {
            $params = [$params];
        }

        $this->db->prepare($sql)
            ->bindParams($params)
            ->execute();

        if (strtoupper(substr($sql, 0, 6)) == 'SELECT') {
            $rows = $this->db->fetchAll();
            foreach ($rows as $i => $row) {
                $rows[$i] = $row;
            }
            $this->setRows($rows, $resultsAs);
        }

        return $this;
    }

    /**
     * Method to execute a custom SQL query.
     *
     * @param  mixed  $sql
     * @param  string $resultsAs
     * @return Collection
     */
    public function query($sql, $resultsAs = 'AS_RESULT')
    {
        if ($sql instanceof Sql) {
            $sql = (string)$sql;
        }

        $this->db->query($sql);

        if (strtoupper(substr($sql, 0, 6)) == 'SELECT') {
            $rows = [];
            while (($row = $this->db->fetch())) {
                $rows[] = $row;
            }
            $this->setRows($rows, $resultsAs);
        }

        return $this;
    }

    /**
     * Method to get the total count of a set from the DB table
     *
     * @param  array  $columns
     * @param  string $resultsAs
     * @return int
     */
    public function getTotal(array $columns = null, $resultsAs = 'AS_RESULT')
    {
        $params = null;
        $where  = null;

        if (null !== $columns) {
            $parsedColumns = Parser\Column::parse($columns, $this->sql->getPlaceholder());
            $params        = $parsedColumns['params'];
            $where         = $parsedColumns['where'];
        }

        $this->setRows($this->tableGateway->select(['total_count' => 'COUNT(1)'], $where, $params), $resultsAs);

        return (int)$this->total_count;
    }

    /**
     * Get table info and return as an array
     *
     * @return array
     */
    public function getTableInfo()
    {
        return $this->tableGateway->getTableInfo();
    }

    /**
     * Set all the table rows at once
     *
     * @param  array  $rows
     * @param  string $resultsAs
     * @return Collection
     */
    public function setRows(array $rows = null, $resultsAs = 'AS_RESULT')
    {
        $this->columns = [];
        $this->rows    = [];

        if (null !== $rows) {
            $this->columns = (isset($rows[0])) ? (array)$rows[0] : [];
            foreach ($rows as $row) {
                switch ($resultsAs) {
                    case self::AS_ARRAY:
                        $this->rows[] = (array)$row;
                        break;
                    case self::AS_OBJECT:
                        $row = (array)$row;
                        foreach ($row as $key => $value) {
                            if (is_array($value)) {
                                foreach ($value as $k => $v) {
                                    $value[$k] = new \ArrayObject((array)$v, \ArrayObject::ARRAY_AS_PROPS);
                                }
                                $row[$key] = $value;
                            }
                        }
                        $this->rows[] = new \ArrayObject((array)$row, \ArrayObject::ARRAY_AS_PROPS);
                        break;
                    default:
                        $row = (array)$row;
                        foreach ($row as $key => $value) {
                            if (is_array($value)) {
                                foreach ($value as $k => $v) {
                                    $value[$k] = new Result($this->db, $this->table, $this->primaryKeys);
                                    $value[$k]->setColumns((array)$v);
                                }
                                $row[$key] = $value;
                            }
                        }
                        $r = new Result($this->db, $this->table, $this->primaryKeys);
                        $r->setColumns((array)$row);
                        $this->rows[] = $r;
                }
            }
        }

        return $this;
    }

    /**
     * Get the rows
     *
     * @return array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Get the rows (alias method)
     *
     * @return array
     */
    public function rows()
    {
        return $this->rows;
    }

    /**
     * Get the count of rows returned in the result
     *
     * @return int
     */
    public function count()
    {
        return count($this->rows);
    }

    /**
     * Determine if the result has rows
     *
     * @return boolean
     */
    public function hasRows()
    {
        return (count($this->rows) > 0);
    }

}