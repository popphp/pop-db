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

use Pop\Db\Adapter;

/**
 * Abstract SQL class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
abstract class AbstractSql
{

    /**
     * Constants for database types
     */
    const MYSQL  = 'MYSQL';
    const PGSQL  = 'PGSQL';
    const SQLITE = 'SQLITE';
    const SQLSRV = 'SQLSRV';

    /**
     * Constants for id quote types
     */
    const BACKTICK     = 'BACKTICK';
    const BRACKET      = 'BRACKET';
    const DOUBLE_QUOTE = 'DOUBLE_QUOTE';
    const NO_QUOTE     = 'NO_QUOTE';

    /**
     * Database object
     * @var Adapter\AbstractAdapter
     */
    protected $db = null;

    /**
     * Database type
     * @var int
     */
    protected $dbType = null;

    /**
     * ID quote type
     * @var string
     */
    protected $idQuoteType = 'NO_QUOTE';

    /**
     * SQL placeholder
     * @var string
     */
    protected $placeholder = '?';

    /**
     * Constructor
     *
     * Instantiate the SQL object
     *
     * @param  Adapter\AbstractAdapter $db
     */
    public function __construct(Adapter\AbstractAdapter $db)
    {
        $this->db = $db;
        $adapter  = strtolower(get_class($db));

        if (strpos($adapter, 'mysql') !== false) {
            $this->dbType      = self::MYSQL;
            $this->idQuoteType = self::BACKTICK;
            $this->placeholder = '?';
        } else if (strpos($adapter, 'pgsql') !== false) {
            $this->dbType      = self::PGSQL;
            $this->idQuoteType = self::DOUBLE_QUOTE;
            $this->placeholder = '$';
        } else if (strpos($adapter, 'sqlite') !== false) {
            $this->dbType      = self::SQLITE;
            $this->idQuoteType = self::DOUBLE_QUOTE;
            $this->placeholder = ':';
        } else if (strpos($adapter, 'sqlsrv') !== false) {
            $this->dbType      = self::SQLSRV;
            $this->idQuoteType = self::BRACKET;
            $this->placeholder = '?';
        } else if (strpos($adapter, 'pdo') !== false) {
            $this->placeholder = ':';
            $type = $this->db->getType();
            if ($type == 'sqlite') {
                $this->dbType      = self::SQLITE;
                $this->idQuoteType = self::DOUBLE_QUOTE;
            } else if ($type == 'pgsql') {
                $this->dbType      = self::PGSQL;
                $this->idQuoteType = self::DOUBLE_QUOTE;
            } else if ($type == 'mysql') {
                $this->dbType      = self::MYSQL;
                $this->idQuoteType = self::BACKTICK;
            }
        }
    }

    /**
     * Determine if the DB type is MySQL
     *
     * @return boolean
     */
    public function isMysql()
    {
        return ($this->dbType == self::MYSQL);
    }

    /**
     * Determine if the DB type is PostgreSQL
     *
     * @return boolean
     */
    public function isPgsql()
    {
        return ($this->dbType == self::PGSQL);
    }

    /**
     * Determine if the DB type is SQL Server
     *
     * @return boolean
     */
    public function isSqlsrv()
    {
        return ($this->dbType == self::SQLSRV);
    }

    /**
     * Determine if the DB type is SQLite
     *
     * @return boolean
     */
    public function isSqlite()
    {
        return ($this->dbType == self::SQLITE);
    }

    /**
     * Get the current database adapter object (alias method)
     *
     * @return Adapter\AbstractAdapter
     */
    public function db()
    {
        return $this->db;
    }

    /**
     * Get the current database adapter object
     *
     * @return Adapter\AbstractAdapter
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Set the quote ID type
     *
     * @param  string $type
     * @return AbstractSql
     */
    public function setIdQuoteType($type = self::NO_QUOTE)
    {
        if (defined('Pop\Db\Sql::' . $type)) {
            $this->idQuoteType = $type;
        }
        return $this;
    }

    /**
     * Set the placeholder
     *
     * @param  string $placeholder
     * @return AbstractSql
     */
    public function setPlaceholder($placeholder)
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * Get the current database type
     *
     * @return int
     */
    public function getDbType()
    {
        return $this->dbType;
    }

    /**
     * Get the quote ID type
     *
     * @return int
     */
    public function getIdQuoteType()
    {
        return $this->idQuoteType;
    }

    /**
     * Get the SQL placeholder
     *
     * @return string
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    /**
     * Quote the identifier
     *
     * @param  string $identifier
     * @return string
     */
    public function quoteId($identifier)
    {
        $quotedId   = null;
        $openQuote  = null;
        $closeQuote = null;

        switch ($this->idQuoteType) {
            case self::BACKTICK:
                $openQuote  = '`';
                $closeQuote = '`';
                break;
            case self::BRACKET:
                $openQuote  = '[';
                $closeQuote = ']';
                break;
            case self::DOUBLE_QUOTE:
                $openQuote  = '"';
                $closeQuote = '"';
                break;
        }

        if (strpos($identifier, '.') !== false) {
            $identifierAry = explode('.', $identifier);
            foreach ($identifierAry as $key => $val) {
                $identifierAry[$key] = ($val != '*') ? $openQuote . $val . $closeQuote : $val;
            }
            $quotedId = implode('.', $identifierAry);
        } else {
            $quotedId = ($identifier != '*') ? $openQuote . $identifier . $closeQuote : $identifier;
        }

        return $quotedId;
    }

    /**
     * Quote the value (if it is not a numeric value)
     *
     * @param  string $value
     * @return string
     */
    public function quote($value)
    {
        if (($value == '') || (($value != '?') && (substr($value, 0, 1) != ':') && (preg_match('/^\$\d*\d$/', $value) == 0) &&
            !is_int($value) && !is_float($value) && (preg_match('/^\d*$/', $value) == 0))) {
            $value = "'" . $this->db->escape($value) . "'";
        }
        return $value;
    }

}