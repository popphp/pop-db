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
namespace Pop\Db;

/**
 * Db class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Db
{

    /**
     * Database connection(s)
     * @var array
     */
    protected static $db = ['default' => null];

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
     * Check the database connection
     *
     * @param  string $adapter
     * @param  array  $options
     * @param  string $prefix
     * @return mixed
     */
    public static function check($adapter, array $options, $prefix = '\Pop\Db\Adapter\\')
    {
        $result = null;
        $class  = $prefix . ucfirst(strtolower($adapter));
        $error  = ini_get('error_reporting');

        error_reporting(E_ERROR);

        try {
            if (!class_exists($class)) {
                $result = 'Error: The database adapter ' . $class . ' does not exist.';
            } else {
                $db     = new $class($options);
            }
        } catch (\Exception $e) {
            $result = $e->getMessage();
        }

        error_reporting($error);
        return $result;
    }

    /**
     * Install a database schema
     *
     * @param  string $sql
     * @param  string $adapter
     * @param  array  $options
     * @param  string $prefix
     * @throws Exception
     * @return void
     */
    public static function install($sql, $adapter, array $options, $prefix = '\Pop\Db\Adapter\\')
    {
        $adapter = ucfirst(strtolower($adapter));
        $class   = $prefix . $adapter;

        if (!class_exists($class)) {
            throw new Exception('Error: The database adapter ' . $class . ' does not exist.');
        }

        // If Sqlite
        if (($adapter == 'Sqlite') || (($adapter == 'Pdo') && isset($options['type'])) && (strtolower($options['type']) == 'sqlite')) {
            if (!file_exists($options['database'])) {
                touch($options['database']);
                chmod($options['database'], 0777);
            }
            if (!file_exists($options['database'])) {
                throw new Exception('Error: Could not create the database file.');
            }
        }

        $db    = new $class($options);
        $lines = file($sql);

        // Remove comments, execute queries
        if (count($lines) > 0) {
            $insideComment = false;
            foreach ($lines as $i => $line) {
                if ($insideComment) {
                    if (substr($line, 0, 2) == '*/') {
                        $insideComment = false;
                    }
                    unset($lines[$i]);
                } else {
                    if ((substr($line, 0, 1) == '-') || (substr($line, 0, 1) == '#')) {
                        unset($lines[$i]);
                    } else if (substr($line, 0, 2) == '/*') {
                        $insideComment = true;
                        unset($lines[$i]);
                    }
                }
            }

            $sqlString  = trim(implode('', $lines));
            $newLine    = (strpos($sqlString, ";\r\n") !== false) ? ";\r\n" : ";\n";
            $statements = explode($newLine, $sqlString);

            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    if (isset($options['prefix'])) {
                        $statement = str_replace('[{prefix}]', $options['prefix'], trim($statement));
                    }
                    $db->query($statement);
                }
            }
        }
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
            'oracle' => (function_exists('oci_connect')),
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
            case 'oci':
            case 'oracle':
                $result = (function_exists('oci_connect'));
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

        if ((!$result) && isset(self::$db['default'])) {
            $result = true;
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
        }

        if ($isDefault) {
            self::$db['default'] = $db;
        }
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
        if ((null !== $class) &&  isset(self::$db[$class])) {
            $dbAdapter = self::$db[$class];
        // Check for database adapter assigned to a namespace
        } else if (null !== $class) {
            foreach (self::$db as $prefix => $adapter) {
                if (substr($class, 0, strlen($prefix)) == $prefix) {
                    $dbAdapter = $adapter;
                }
            }
        }

        if ((null === $dbAdapter) && isset(self::$db['default'])) {
            $dbAdapter =  self::$db['default'];
        }

        if (null === $dbAdapter) {
            throw new Exception('No database adapter was found.');
        }

        return $dbAdapter;
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

}