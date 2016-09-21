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
namespace Pop\Db\Adapter;

/**
 * PDO database adapter class
 *
 * @category   Pop
 * @package    Pop_Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Pdo extends AbstractAdapter
{

    /**
     * PDO DSN
     * @var string
     */
    protected $dsn = null;

    /**
     * PDO database type
     * @var string
     */
    protected $type = null;

    /**
     * Constructor
     *
     * Instantiate the database connection object using PDO
     *
     * @param  array $options
     * @throws Exception
     * @return Pdo
     */
    public function __construct(array $options)
    {
        if (!isset($options['host'])) {
            $options['host'] = 'localhost';
        }

        if (!isset($options['type']) || !isset($options['database'])) {
            throw new Exception('Error: The proper database credentials were not passed.');
        }

        try {
            $this->type = strtolower($options['type']);
            if ($this->type == 'sqlite') {
                $this->dsn = $this->type . ':' . $options['database'];
                if (isset($options['options']) && is_array($options['options'])) {
                    $this->connection = new \PDO($this->dsn, null, null, $options['options']);
                } else {
                    $this->connection = new \PDO($this->dsn);
                }
            } else {
                if (!isset($options['host']) || !isset($options['username']) || !isset($options['password'])) {
                    throw new Exception('Error: The proper database credentials were not passed.');
                }

                $this->dsn = ($this->type == 'sqlsrv') ?
                    $this->type . ':Server=' . $options['host'] . ';Database=' . $options['database'] :
                    $this->type . ':host=' . $options['host'] . ';dbname=' . $options['database'];

                if (isset($options['options']) && is_array($options['options'])) {
                    $this->connection = new \PDO($this->dsn, $options['username'], $options['password'], $options['options']);
                } else {
                    $this->connection = new \PDO($this->dsn, $options['username'], $options['password']);
                }
            }
        } catch (\PDOException $e) {
            $this->setError('PDO Connection Error: ' . $e->getMessage() . ' (#' . $e->getCode() . ')')
                 ->throwError();
        }
    }

    /**
     * Return the DSN
     *
     * @return string
     */
    public function getDsn()
    {
        return $this->dsn;
    }

    /**
     * Return the database version
     *
     * @return string
     */
    public function getVersion()
    {
        return 'PDO ' . substr($this->dsn, 0, strpos($this->dsn, ':')) . ' ' .
            $this->connection->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Disconnect from the database
     *
     * @return void
     */
    public function disconnect()
    {
        parent::disconnect();
    }

}