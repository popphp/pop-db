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
namespace Pop\Db\Adapter\Profiler;

/**
 * MySQL database adapter profiler step class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Step extends AbstractProfiler
{

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
     * Set query
     *
     * @param  string $sql
     * @return Step
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
     * @return Step
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
     * @return Step
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
     * @return Step
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
     * Magic method to support shorthand calls to certain values in the step
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
            case 'params':
                return $this->params;
                break;
            case 'errors':
                return $this->errors;
                break;
            case 'start':
                return $this->start;
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