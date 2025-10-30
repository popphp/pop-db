<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.7.0
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
     * Supported standard SQL aggregate functions
     * @var array
     */
    protected static array $aggregateFunctions = [
        'AVG', 'COUNT', 'MAX', 'MIN', 'SUM'
    ];

    /**
     * Supported standard SQL math functions
     * @var array
     */
    protected static array $mathFunctions = [
        'ABS', 'RAND', 'SQRT', 'POW', 'POWER', 'EXP', 'LN', 'LOG', 'LOG10', 'GREATEST', 'LEAST',
        'DIV', 'MOD', 'ROUND', 'TRUNC', 'CEIL', 'CEILING', 'FLOOR', 'COS', 'ACOS', 'ACOSH', 'SIN',
        'SINH', 'ASIN', 'ASINH', 'TAN', 'TANH', 'ATANH', 'ATAN2',
    ];

    /**
     * Supported standard SQL string functions
     * @var array
     */
    protected static array $stringFunctions = [
        'CONCAT', 'FORMAT', 'INSTR', 'LCASE', 'LEFT', 'LENGTH', 'LOCATE', 'LOWER', 'LPAD',
        'LTRIM', 'POSITION', 'QUOTE', 'REGEXP', 'REPEAT', 'REPLACE', 'REVERSE', 'RIGHT', 'RPAD',
        'RTRIM', 'SPACE', 'STRCMP', 'SUBSTRING', 'SUBSTR', 'TRIM', 'UCASE', 'UPPER'
    ];

    /**
     * Supported standard SQL date-time functions
     * @var array
     */
    protected static array $dateTimeFunctions = [
        'CURRENT_DATE', 'CURRENT_TIMESTAMP', 'CURRENT_TIME', 'CURDATE', 'CURTIME', 'DATE', 'DATETIME',
        'DAY', 'EXTRACT', 'GETDATE', 'HOUR', 'LOCALTIME', 'LOCALTIMESTAMP', 'MINUTE', 'MONTH',
        'NOW', 'SECOND', 'TIME', 'TIMEDIFF', 'TIMESTAMP', 'UNIX_TIMESTAMP', 'YEAR',
    ];

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

        // If the parameter is given in a different format than what the db expects, translate it
        $realDbType = $this->dbType;
        if ($this->placeholder == ':') {
            // Either native SQLITE or PDO, in which case also use :param syntax
            $realDbType = self::SQLITE;
        }

        if (($detectedDbType !== null) && ($realDbType != $detectedDbType)) {
            switch ($realDbType) {
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
        } else if (($identifier != '*') &&
            ((preg_match('/^\$\d*\d$/', $identifier) == 0) && !is_int($identifier) &&
                !is_float($identifier) && (preg_match('/^\d*$/', $identifier) == 0))) {
            $quotedId = $this->openQuote . $identifier . $this->closeQuote;
        } else {
            $quotedId = $identifier;
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
                (($value != '?') &&
                    (!empty($this->openQuote) && !empty($this->closeQuote) &&
                        !(str_starts_with($value, $this->openQuote) && str_ends_with($value, $this->closeQuote))) &&
                    (!str_starts_with($value, ':')) && (preg_match('/^\$\d*\d$/', $value) == 0) &&
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

    /**
     * Check if value contains a standard SQL supported function
     *
     * @param  string $value
     * @return bool
     */
    public static function isSupportedFunction(string $value): bool
    {
        if (str_contains($value, '(')) {
            $value = trim(substr($value, 0, strpos($value, '(')));
        }
        $value = strtoupper($value);

        return (in_array($value, static::$aggregateFunctions) ||
            in_array($value, static::$mathFunctions) ||
            in_array($value, static::$stringFunctions) ||
            in_array($value, static::$dateTimeFunctions));
    }

}
