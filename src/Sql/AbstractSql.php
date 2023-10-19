<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    6.0.0
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
     * @var ?Adapter\AbstractAdapter
     */
    protected ?Adapter\AbstractAdapter $db = null;

    /**
     * Database type
     * @var ?string
     */
    protected ?string $dbType = null;

    /**
     * SQL placeholder
     * @var ?string
     */
    protected ?string $placeholder = null;

    /**
     * ID quote type
     * @var string
     */
    protected string $idQuoteType = 'NO_QUOTE';

    /**
     * ID open quote
     * @var ?string
     */
    protected ?string $openQuote = null;

    /**
     * ID close quote
     * @var ?string
     */
    protected ?string $closeQuote = null;

    /**
     * Parameter count
     * @var int
     */
    protected int $parameterCount = 0;

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
     * @return bool
     */
    public function isMysql(): bool
    {
        return ($this->dbType == self::MYSQL);
    }

    /**
     * Determine if the DB type is PostgreSQL
     *
     * @return bool
     */
    public function isPgsql(): bool
    {
        return ($this->dbType == self::PGSQL);
    }

    /**
     * Determine if the DB type is SQL Server
     *
     * @return bool
     */
    public function isSqlsrv(): bool
    {
        return ($this->dbType == self::SQLSRV);
    }

    /**
     * Determine if the DB type is SQLite
     *
     * @return bool
     */
    public function isSqlite(): bool
    {
        return ($this->dbType == self::SQLITE);
    }

    /**
     * Get the current database adapter object (alias method)
     *
     * @return ?Adapter\AbstractAdapter
     */
    public function db(): ?Adapter\AbstractAdapter
    {
        return $this->db;
    }

    /**
     * Get the current database adapter object
     *
     * @return ?Adapter\AbstractAdapter
     */
    public function getDb(): ?Adapter\AbstractAdapter
    {
        return $this->db;
    }

    /**
     * Set the quote ID type
     *
     * @param  string $type
     * @return AbstractSql
     */
    public function setIdQuoteType(string $type = self::NO_QUOTE): AbstractSql
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
    public function setPlaceholder(string $placeholder): AbstractSql
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * Get the current database type
     *
     * @return ?string
     */
    public function getDbType(): ?string
    {
        return $this->dbType;
    }

    /**
     * Get the SQL placeholder
     *
     * @return ?string
     */
    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    /**
     * Get the quote ID type
     *
     * @return string
     */
    public function getIdQuoteType(): string
    {
        return $this->idQuoteType;
    }

    /**
     * Get open quote
     *
     * @return ?string
     */
    public function getOpenQuote(): ?string
    {
        return $this->openQuote;
    }

    /**
     * Get close quote
     *
     * @return ?string
     */
    public function getCloseQuote(): ?string
    {
        return $this->closeQuote;
    }

    /**
     * Get parameter count
     *
     * @return int
     */
    public function getParameterCount(): int
    {
        return $this->parameterCount;
    }

    /**
     * Increment parameter count
     *
     * @return AbstractSql
     */
    public function incrementParameterCount(): AbstractSql
    {
        $this->parameterCount++;
        return $this;
    }

    /**
     * Decrement parameter count
     *
     * @return AbstractSql
     */
    public function decrementParameterCount(): AbstractSql
    {
        $this->parameterCount--;
        return $this;
    }

    /**
     * Check if value is parameter placeholder
     *
     * @param  mixed   $value
     * @param  ?string $column
     * @return bool
     */
    public function isParameter(mixed $value, ?string $column = null): bool
    {
        return ((!empty($value) && ($column !== null) && ((':' . $column) == $value)) ||
                ((preg_match('/^\$\d*\d$/', (string)$value) == 1)) ||
                (($value == '?')));
    }

    /**
     * Get parameter placeholder value
     *
     * @param  mixed   $value
     * @param  ?string $column
     * @return string
     */
    public function getParameter(mixed $value, ?string $column = null): string
    {
        $detectedDbType = null;
        $parameter      = $value;

        // SQLITE
        if (($column !== null) && ((':' . $column) == $value)) {
            $detectedDbType = self::SQLITE;
        // PGSQL
        } else if (preg_match('/^\$\d*\d$/', $value) == 1) {
            $detectedDbType = self::PGSQL;
        // MYSQL/SQLSRV
        } else if ($value == '?') {
            $detectedDbType = self::MYSQL;
        }

        $this->incrementParameterCount();

        if (($detectedDbType !== null) && ($this->dbType != $detectedDbType)) {
            switch ($this->dbType) {
                case self::MYSQL:
                case self::SQLSRV:
                    $parameter = '?';
                    break;
                case self::PGSQL:
                    $parameter = '$' . $this->parameterCount;
                    break;
                case self::SQLITE:
                    if ($column !== null) {
                        $parameter = ':' . $column;
                    }
                    break;
            }
        }

        return $parameter;
    }

    /**
     * Quote the identifier
     *
     * @param  string $identifier
     * @return string
     */
    public function quoteId(string $identifier): string
    {
        $quotedId = null;

        if (str_contains($identifier, '.')) {
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
     * @param  ?string $value
     * @param  bool    $force
     * @return float|int|string
     */
    public function quote(?string $value = null, bool $force = false): float|int|string
    {
        if ($force) {
            if (($value == '') ||
                ((preg_match('/^\$\d*\d$/', $value) == 0) &&
                    !is_int($value) && !is_float($value) && (preg_match('/^\d*$/', $value) == 0))) {
                $value = "'" . $this->db->escape($value) . "'";
            }
        } else {
            if (($value == '') ||
                (($value != '?') && (!str_starts_with($value, ':')) && (preg_match('/^\$\d*\d$/', $value) == 0) &&
                    !is_int($value) && !is_float($value) && (preg_match('/^\d*$/', $value) == 0))) {
                $value = "'" . $this->db->escape($value) . "'";
            }
        }

        return $value;
    }

    /**
     * Initialize SQL object
     *
     * @param  string $adapter
     * @return void
     */
    protected function init(string $adapter): void
    {
        if (stripos($adapter, 'pdo') !== false) {
            $adapter           = $this->db->getType();
            $this->placeholder = ':';
        }

        if (stripos($adapter, 'mysql') !== false) {
            $this->dbType      = self::MYSQL;
            $this->idQuoteType = self::BACKTICK;
            if ($this->placeholder === null) {
                $this->placeholder = '?';
            }
        } else if (stripos($adapter, 'pgsql') !== false) {
            $this->dbType      = self::PGSQL;
            $this->idQuoteType = self::DOUBLE_QUOTE;
            if ($this->placeholder === null) {
                $this->placeholder = '$';
            }
        } else if (stripos($adapter, 'sqlite') !== false) {
            $this->dbType      = self::SQLITE;
            $this->idQuoteType = self::DOUBLE_QUOTE;
            if ($this->placeholder === null) {
                $this->placeholder = ':';
            }
        } else if (stripos($adapter, 'sqlsrv') !== false) {
            $this->dbType      = self::SQLSRV;
            $this->idQuoteType = self::BRACKET;
            if ($this->placeholder === null) {
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
    protected function initQuoteType(): void
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