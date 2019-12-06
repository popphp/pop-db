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

use Pop\Db\Adapter;

/**
 * Abstract SQL class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
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
     * SQL placeholder
     * @var string
     */
    protected $placeholder = null;

    /**
     * ID quote type
     * @var string
     */
    protected $idQuoteType = 'NO_QUOTE';

    /**
     * ID open quote
     * @var string
     */
    protected $openQuote = null;

    /**
     * ID close quote
     * @var string
     */
    protected $closeQuote = null;

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
        $this->init(strtolower(get_class($db)));
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
            $this->initQuoteType();
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
     * Get the SQL placeholder
     *
     * @return string
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
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
     * Get open quote
     *
     * @return string
     */
    public function getOpenQuote()
    {
        return $this->openQuote;
    }

    /**
     * Get close quote
     *
     * @return string
     */
    public function getCloseQuote()
    {
        return $this->closeQuote;
    }

    /**
     * Quote the identifier
     *
     * @param  string $identifier
     * @return string
     */
    public function quoteId($identifier)
    {
        $quotedId = null;

        if (strpos($identifier, '.') !== false) {
            $identifierAry = explode('.', $identifier);
            foreach ($identifierAry as $key => $val) {
                $identifierAry[$key] = ($val != '*') ? $this->openQuote . $val . $this->closeQuote : $val;
            }
            $quotedId = implode('.', $identifierAry);
        } else {
            $quotedId = ($identifier != '*') ? $this->openQuote . $identifier . $this->closeQuote : $identifier;
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
        if (($value == '') ||
            (($value != '?') && (substr($value, 0, 1) != ':') && (preg_match('/^\$\d*\d$/', $value) == 0) &&
            !is_int($value) && !is_float($value) && (preg_match('/^\d*$/', $value) == 0))) {
            $value = "'" . $this->db->escape($value) . "'";
        }
        return $value;
    }

    /**
     * Initialize SQL object
     *
     * @param  string $adapter
     * @return void
     */
    protected function init($adapter)
    {
        if (stripos($adapter, 'pdo') !== false) {
            $adapter           = $this->db->getType();
            $this->placeholder = ':';
        }

        if (stripos($adapter, 'mysql') !== false) {
            $this->dbType      = self::MYSQL;
            $this->idQuoteType = self::BACKTICK;
            if (null === $this->placeholder) {
                $this->placeholder = '?';
            }
        } else if (stripos($adapter, 'pgsql') !== false) {
            $this->dbType      = self::PGSQL;
            $this->idQuoteType = self::DOUBLE_QUOTE;
            if (null === $this->placeholder) {
                $this->placeholder = '$';
            }
        } else if (stripos($adapter, 'sqlite') !== false) {
            $this->dbType      = self::SQLITE;
            $this->idQuoteType = self::DOUBLE_QUOTE;
            if (null === $this->placeholder) {
                $this->placeholder = ':';
            }
        } else if (stripos($adapter, 'sqlsrv') !== false) {
            $this->dbType      = self::SQLSRV;
            $this->idQuoteType = self::BRACKET;
            if (null === $this->placeholder) {
                $this->placeholder = '?';
            }
        }

        $this->initQuoteType();
    }

    /**
     * Initialize quite type
     *
     * @return void
     */
    protected function initQuoteType()
    {
        switch ($this->idQuoteType) {
            case (self::BACKTICK):
                $this->openQuote   = '`';
                $this->closeQuote  = '`';
                break;
            case (self::DOUBLE_QUOTE):
                $this->openQuote   = '"';
                $this->closeQuote  = '"';
                break;
            case (self::BRACKET):
                $this->openQuote   = '[';
                $this->closeQuote  = ']';
                break;
            case (self::NO_QUOTE):
                $this->openQuote   = null;
                $this->closeQuote  = null;
                break;
        }
    }

}