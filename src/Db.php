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
namespace Pop\Db;

/**
 * Db class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Db
{

    /**
     * Database connection(s)
     * @var array
     */
    protected static $db = ['default' => null];

    /**
     * Database connection class to table relationship
     * @var array
     */
    protected static $classToTable = [];

    /**
     * Method to connect to a database and return the database adapter object
     *
     * @param  string $adapter
     * @param  array  $options
     * @param  string $prefix
     * @throws Exception
     * @return Adapter\AbstractAdapter
     */
    public static function connect($adapter, array $options, $prefix = '\Pop\Db\Adapter\\')
    {
        $class = $prefix . ucfirst(strtolower($adapter));

        if (!class_exists($class)) {
            throw new Exception('Error: The database adapter ' . $class . ' does not exist.');
        }

        return new $class($options);
    }

    /**
     * Method to connect to a MySQL database and return the MySQL database adapter object
     *
     * @param  array  $options
     * @param  string $prefix
     * @throws Exception
     * @return Adapter\Mysql|Adapter\AbstractAdapter
     */
    public static function mysqlConnect(array $options, $prefix = '\Pop\Db\Adapter\\')
    {
        return self::connect('mysql', $options, $prefix);
    }

    /**
     * Method to connect to a PDO database and return the PDO database adapter object
     *
     * @param  array  $options
     * @param  string $prefix
     * @throws Exception
     * @return Adapter\Pdo|Adapter\AbstractAdapter
     */
    public static function pdoConnect(array $options, $prefix = '\Pop\Db\Adapter\\')
    {
        return self::connect('pdo', $options, $prefix);
    }

    /**
     * Method to connect to a PostgreSQL database and return the PostgreSQL database adapter object
     *
     * @param  array  $options
     * @param  string $prefix
     * @throws Exception
     * @return Adapter\Pgsql|Adapter\AbstractAdapter
     */
    public static function pgsqlConnect(array $options, $prefix = '\Pop\Db\Adapter\\')
    {
        return self::connect('pgsql', $options, $prefix);
    }

    /**
     * Method to connect to a SQL Server database and return the SQL Server database adapter object
     *
     * @param  array  $options
     * @param  string $prefix
     * @throws Exception
     * @return Adapter\Sqlsrv|Adapter\AbstractAdapter
     */
    public static function sqlsrvConnect(array $options, $prefix = '\Pop\Db\Adapter\\')
    {
        return self::connect('sqlsrv', $options, $prefix);
    }

    /**
     * Method to connect to a SQLite database and return the SQLite database adapter object
     *
     * @param  array  $options
     * @param  string $prefix
     * @throws Exception
     * @return Adapter\Sqlite|Adapter\AbstractAdapter
     */
    public static function sqliteConnect(array $options, $prefix = '\Pop\Db\Adapter\\')
    {
        return self::connect('sqlite', $options, $prefix);
    }

    /**
     * Check the database connection
     *
     * @param  string $adapter
     * @param  array  $options
     * @param  string $prefix
     * @return mixed
     */
    public static function check($adapter, array $options, $prefix = '\Pop\Db\Adapter\\')
    {
        $result = true;
        $class  = $prefix . ucfirst(strtolower($adapter));
        $error  = ini_get('error_reporting');

        error_reporting(E_ERROR);

        try {
            if (!class_exists($class)) {
                $result = "Error: The database adapter '" . $class . "' does not exist.";
            } else {
                $db = new $class($options);
            }
        } catch (\Exception $e) {
            $result = $e->getMessage();
        }

        error_reporting($error);
        return $result;
    }

    /**
     * Execute SQL
     *
     * @param  string $sql
     * @param  mixed  $adapter
     * @param  array  $options
     * @param  string $prefix
     * @throws Exception
     * @return void
     */
    public static function executeSql($sql, $adapter, array $options = [], $prefix = '\Pop\Db\Adapter\\')
    {
        if (is_string($adapter)) {
            $adapter = ucfirst(strtolower($adapter));
            $class   = $prefix . $adapter;

            if (!class_exists($class)) {
                throw new Exception('Error: The database adapter ' . $class . ' does not exist.');
            }

            // If Sqlite
            if (($adapter == 'Sqlite') ||
                (($adapter == 'Pdo') && isset($options['type'])) && (strtolower($options['type']) == 'sqlite')) {
                if (!file_exists($options['database'])) {
                    touch($options['database']);
                    chmod($options['database'], 0777);
                }
                if (!file_exists($options['database'])) {
                    throw new Exception('Error: Could not create the database file.');
                }
            }

            $db = new $class($options);
        } else {
            $db = $adapter;
        }

        $lines      = explode("\n", $sql);
        $statements = [];

        if (count($lines) > 0) {
            // Remove any comments, parse prefix if available
            $insideComment = false;
            foreach ($lines as $i => $line) {
                if (empty($line)) {
                    unset($lines[$i]);
                } else {
                    if (isset($options['prefix'])) {
                        $lines[$i] = str_replace('[{prefix}]', $options['prefix'], trim($line));
                    }
                    if ($insideComment) {
                        if (substr($line, -2) == '*/') {
                            $insideComment = false;
                        }
                        unset($lines[$i]);
                    } else {
                        if ((substr($line, 0, 1) == '-') || (substr($line, 0, 1) == '#')) {
                            unset($lines[$i]);
                        } else if (substr($line, 0, 2) == '/*') {
                            $line = trim($line);
                            if ((substr($line, -2) != '*/') && (substr($line, -3) != '*/;')) {
                                $insideComment = true;
                            }
                            unset($lines[$i]);
                        } else if (strrpos($line, '--') !== false) {
                            $lines[$i] = substr($line, 0, strrpos($line, '--'));
                        } else if (strrpos($line, '/*') !== false) {
                            $lines[$i] = substr($line, 0, strrpos($line, '/*'));
                        }
                    }
                }
            }

            $lines            = array_values(array_filter($lines));
            $currentStatement = null;

            // Assemble statements based on ; delimiter
            foreach ($lines as $i => $line) {
                $currentStatement .= (null !== $currentStatement) ? ' ' . $line : $line;
                if (substr($line, -1) == ';') {
                    $statements[]     = $currentStatement;
                    $currentStatement = null;
                }
            }

            if (!empty($statements)) {
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $db->query($statement);
                    }
                }
            }
        }
    }

    /**
     * Execute SQL
     *
     * @param  string $sqlFile
     * @param  mixed  $adapter
     * @param  array  $options
     * @param  string $prefix
     * @throws Exception
     * @return void
     */
    public static function executeSqlFile($sqlFile, $adapter, array $options = [], $prefix = '\Pop\Db\Adapter\\')
    {
        if (!file_exists($sqlFile)) {
            throw new Exception("Error: The SQL file '" . $sqlFile . "' does not exist.");
        }

        self::executeSql(file_get_contents($sqlFile), $adapter, $options, $prefix);
    }

    /**
     * Get the available database adapters
     *
     * @return array
     */
    public static function getAvailableAdapters()
    {
        $pdoDrivers = (class_exists('Pdo', false)) ? \PDO::getAvailableDrivers() : [];

        return [
            'mysqli' => (class_exists('mysqli', false)),
            'pdo'    => [
                'mysql'  => (in_array('mysql', $pdoDrivers)),
                'pgsql'  => (in_array('pgsql', $pdoDrivers)),
                'sqlite' => (in_array('sqlite', $pdoDrivers)),
                'sqlsrv' => (in_array('sqlsrv', $pdoDrivers))
            ],
            'pgsql'  => (function_exists('pg_connect')),
            'sqlite' => (class_exists('Sqlite3', false)),
            'sqlsrv' => (function_exists('sqlsrv_connect'))
        ];
    }

    /**
     * Determine if a database adapter is available
     *
     * @param  string $adapter
     * @return boolean
     */
    public static function isAvailable($adapter)
    {
        $adapter = strtolower($adapter);
        $result  = false;
        $type    = null;

        $pdoDrivers = (class_exists('Pdo', false)) ? \PDO::getAvailableDrivers() : [];
        if (strpos($adapter, 'pdo_') !== false) {
            $type    = substr($adapter, 4);
            $adapter = 'pdo';
        }

        switch ($adapter) {
            case 'mysql':
            case 'mysqli':
                $result = (class_exists('mysqli', false));
                break;
            case 'pdo':
                $result = (in_array($type, $pdoDrivers));
                break;
            case 'pgsql':
                $result = (function_exists('pg_connect'));
                break;
            case 'sqlite':
                $result = (class_exists('Sqlite3', false));
                break;
            case 'sqlsrv':
                $result = (function_exists('sqlsrv_connect'));
                break;
        }

        return $result;
    }

    /**
     * Set DB adapter
     *
     * @param  Adapter\AbstractAdapter $db
     * @param  string                  $class
     * @param  string                  $prefix
     * @param  boolean                 $isDefault
     * @return void
     */
    public static function setDb(Adapter\AbstractAdapter $db, $class = null, $prefix = null, $isDefault = false)
    {
        if (null !== $prefix) {
            self::$db[$prefix] = $db;
        }

        if (null !== $class) {
            self::$db[$class] = $db;
            $record = new $class();
            if ($record instanceof Record) {
                self::$classToTable[$class] = $record->getFullTable();
            }
        }

        if ($isDefault) {
            self::$db['default'] = $db;
        }
    }

    /**
     * Get DB adapter
     *
     * @param  string $class
     * @throws Exception
     * @return Adapter\AbstractAdapter
     */
    public static function getDb($class = null)
    {
        $dbAdapter = null;

        // Check for database adapter assigned to a full class name
        if ((null !== $class) && isset(self::$db[$class])) {
            $dbAdapter = self::$db[$class];
        // Check for database adapter assigned to a namespace
        } else if (null !== $class) {
            foreach (self::$db as $prefix => $adapter) {
                if (substr($class, 0, strlen($prefix)) == $prefix) {
                    $dbAdapter = $adapter;
                }
            }
        }

        // Check if class is actual table name
        if ((null === $dbAdapter) && (null !== $class) && in_array($class, self::$classToTable)) {
            $class = array_search($class, self::$classToTable);
            // Direct match
            if (isset(self::$db[$class])) {
                $dbAdapter = self::$db[$class];
            // Check prefixes
            } else {
                foreach (self::$db as $prefix => $adapter) {
                    if (substr($class, 0, strlen($prefix)) == $prefix) {
                        $dbAdapter = $adapter;
                    }
                }
            }
        }

        if ((null === $dbAdapter) && isset(self::$db['default'])) {
            $dbAdapter = self::$db['default'];
        }

        if (null === $dbAdapter) {
            throw new Exception('No database adapter was found.');
        }

        return $dbAdapter;
    }

    /**
     * Check for a DB adapter
     *
     * @param  string $class
     * @return boolean
     */
    public static function hasDb($class = null)
    {
        $result = false;

        if ((null !== $class) && isset(self::$db[$class])) {
            $result = true;
        } else if (null !== $class) {
            foreach (self::$db as $prefix => $adapter) {
                if (substr($class, 0, strlen($prefix)) == $prefix) {
                    $result = true;
                }
            }
        }

        if ((!$result) && (null !== $class) && in_array($class, self::$classToTable)) {
            $table = array_search($class, self::$classToTable);
            if (isset(self::$db[$table])) {
                $result = true;
            }
        }

        if ((!$result) && isset(self::$db['default'])) {
            $result = true;
        }

        return $result;
    }

    /**
     * Add class-to-table relationship
     *
     * @param  string $class
     * @param  string $table
     * @return void
     */
    public static function addClassToTable($class, $table)
    {
        self::$classToTable[$class] = $table;
    }

    /**
     * Check if class-to-table relationship exists
     *
     * @param  string $class
     * @return boolean
     */
    public static function hasClassToTable($class)
    {
        return isset(self::$classToTable[$class]);
    }

    /**
     * Set DB adapter
     *
     * @param  Adapter\AbstractAdapter $db
     * @param  string                  $class
     * @param  string                  $prefix
     * @return void
     */
    public static function setDefaultDb(Adapter\AbstractAdapter $db, $class = null, $prefix = null)
    {
        self::setDb($db, $class, $prefix, true);
    }

    /**
     * Get DB adapter (alias)
     *
     * @param  string $class
     * @return Adapter\AbstractAdapter
     */
    public static function db($class = null)
    {
        return self::getDb($class);
    }

    /**
     * Get all DB adapters
     *
     * @return array
     */
    public static function getAll()
    {
        return self::$db;
    }

}