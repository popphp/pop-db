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
namespace Pop\Db\Adapter\Profiler;

/**
 * MySQL database adapter profiler step class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.8.0
 */
class Step extends AbstractProfiler
{

    /**
     * Query SQL
     * @var ?string
     */
    protected ?string $query = null;

    /**
     * Statement parameters
     * @var array
     */
    protected array $params = [];

    /**
     * Errors
     * @var array
     */
    protected array $errors = [];

    /**
     * Set query
     *
     * @param  string $sql
     * @return Step
     */
    public function setQuery(string $sql): Step
    {
        $this->query = $sql;
        return $this;
    }

    /**
     * Determine if the profiler has query
     *
     * @return bool
     */
    public function hasQuery(): bool
    {
        return ($this->query !== null);
    }

    /**
     * Get query
     *
     * @return ?string
     */
    public function getQuery(): ?string
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
    public function addParam(string $name, mixed $value): Step
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
    public function addParams(array $params): Step
    {
        foreach ($params as $name => $value) {
            $this->addParam($name, $value);
        }
        return $this;
    }

    /**
     * Determine if the profiler has params
     *
     * @return bool
     */
    public function hasParams(): bool
    {
        return (count($this->params) > 0);
    }

    /**
     * Get params
     *
     * @return array
     */
    public function getParams(): array
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
    public function addError(string $error, mixed $number = null): Step
    {
        $this->errors[(string)microtime(true)] = [
            'error'  => $error,
            'number' => $number
        ];

        return $this;
    }

    /**
     * Determine if the profiler has errors
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return (count($this->errors) > 0);
    }

    /**
     * Get errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Magic method to support shorthand calls to certain values in the step
     *
     * @param  string $name
     * @return mixed
     */
    public function __get(string $name): mixed
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
