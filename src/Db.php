<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2015 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db;

/**
 * Db install class
 *
 * @category   Pop
 * @package    Pop_Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2015 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.0.0
 */
class Db
{

    /**
     * Factory to create database adapter object and connect to the database
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
            throw new Exception('Error: The database adapter ' . $class . ' is not valid.');
        }
        return new $class($options);
    }

    /**
     * Check the database
     *
     * @param  array  $db
     * @param  string $adapter
     * @param  string $prefix
     * @return string
     */
    public static function check($db, $adapter, $prefix = '\Pop\Db\Adapter\\')
    {
        $error = ini_get('error_reporting');
        error_reporting(E_ERROR);

        try {
            // Test the db connection
            $class = $prefix . $adapter;
            if (!class_exists($class)) {
                return 'The database adapter ' . $class . ' is not valid.';
            } else {
                $conn = new $class($db);
            }
            error_reporting($error);
            return null;
        } catch (\Exception $e) {
            error_reporting($error);
            return $e->getMessage();
        }
    }

    /**
     * Install the database schema
     *
     * @param  string $sql
     * @param  array  $db
     * @param  string $adapter
     * @param  string $prefix
     * @throws Exception
     * @return void
     */
    public static function install($sql, $db, $adapter, $prefix = '\Pop\Db\Adapter\\')
    {
        $class = $prefix . $adapter;
        if (!class_exists($class)) {
            throw new Exception('The database adapter ' . $class . ' is not valid.');
        }
        // If Sqlite
        if (($adapter == 'Sqlite') || (($adapter == 'Pdo') && isset($db['type'])) && (strtolower($db['type']) == 'sqlite')) {
            if (!file_exists($db['database'])) {
                touch($db['database']);
                chmod($db['database'], 0777);
            }
            if (!file_exists($db['database'])) {
                throw new Exception('Error: Could not create the database file.');
            }
        }

        $conn  = new $class($db);
        $lines = file($sql);

        // Remove comments, execute queries
        if (count($lines) > 0) {
            foreach ($lines as $i => $line) {
                if (substr($line, 0, 1) == '-') {
                    unset($lines[$i]);
                }
            }
            $sqlString  = trim(implode('', $lines));
            $newLine    = (strpos($sqlString, ";\r\n") !== false) ? ";\r\n" : ";\n";
            $statements = explode($newLine, $sqlString);

            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    if (isset($db['prefix'])) {
                        $statement = str_replace('[{prefix}]', $db['prefix'], trim($statement));
                    }
                    $conn->query($statement);
                }
            }
        }
    }

}
