<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Adapter\Profiler;

/**
 * Db abstract adapter profiler class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
abstract class AbstractProfiler implements ProfilerInterface
{

    /**
     * Profiler start time
     * @var int
     */
    protected $start = null;

    /**
     * Profiler finish time
     * @var int
     */
    protected $finish = null;

    /**
     * Query SQL
     * @var string
     */
    protected $query = null;

    /**
     * Statement parameters
     * @var array
     */
    protected $params = [];

    /**
     * Errors
     * @var array
     */
    protected $errors = [];

    /**
     * Constructor
     *
     * Instantiate the profiler object
     */
    public function __construct()
    {
        $this->start = microtime(true);
    }

    /**
     * Get start
     *
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set query
     *
     * @param  string $sql
     * @return AbstractProfiler
     */
    public function setQuery($sql)
    {
        $this->query = $sql;
        return $this;
    }

    /**
     * Determine if the profiler has query
     *
     * @return boolean
     */
    public function hasQuery()
    {
        return (null !== $this->query);
    }

    /**
     * Get query
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Add param
     *
     * @param  string $name
     * @param  mixed  $value
     * @return AbstractProfiler
     */
    public function addParam($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Add params
     *
     * @param  array $params
     * @return AbstractProfiler
     */
    public function addParams(array $params)
    {
        foreach ($params as $name => $value) {
            $this->addParam($name, $value);
        }
        return $this;
    }

    /**
     * Determine if the profiler has params
     *
     * @return boolean
     */
    public function hasParams()
    {
        return (count($this->params) > 0);
    }

    /**
     * Get params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Add error
     *
     * @param  string $error
     * @param  mixed  $number
     * @return AbstractProfiler
     */
    public function addError($error, $number = null)
    {
        $this->errors[microtime(true)] = [
            'error'  => $error,
            'number' => $number
        ];

        return $this;
    }

    /**
     * Determine if the profiler has errors
     *
     * @return boolean
     */
    public function hasErrors()
    {
        return (count($this->errors) > 0);
    }

    /**
     * Get errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Finish profiler
     *
     * @return ProfilerInterface
     */
    public function finish()
    {
        $this->finish = microtime(true);
        return $this;
    }

    /**
     * Get finish
     *
     * @return int
     */
    public function getFinish()
    {
        return $this->finish;
    }

    /**
     * Get elapsed time
     *
     * @return int
     */
    public function getElapsed()
    {
        return ($this->finish - $this->start);
    }

    /**
     * Magic method to support shorthand calls to certain values in the profiler
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'query':
                return $this->query;
                break;
            case 'statement':
                return $this->statement;
                break;
            case 'params':
                return $this->params;
                break;
            case 'errors':
                return $this->errors;
                break;
            case 'start':
                return $this->start;
                break;
            case 'execution':
                return $this->execution;
                break;
            case 'finish':
                return $this->finish;
                break;
            case 'elapsed':
                return $this->getElapsed();
                break;
            default:
                return null;
        }
    }

}