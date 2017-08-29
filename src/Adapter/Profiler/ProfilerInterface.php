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
 * Db adapter profiler interface
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
interface ProfilerInterface
{

    /**
     * Get start
     *
     * @return int
     */
    public function getStart();

    /**
     * Set query
     *
     * @param  string $sql
     * @return ProfilerInterface
     */
    public function setQuery($sql);

    /**
     * Determine if the profiler has query
     *
     * @return boolean
     */
    public function hasQuery();

    /**
     * Get query
     *
     * @return string
     */
    public function getQuery();

    /**
     * Add param
     *
     * @param  string $name
     * @param  mixed  $value
     * @return ProfilerInterface
     */
    public function addParam($name, $value);

    /**
     * Add params
     *
     * @param  array $params
     * @return ProfilerInterface
     */
    public function addParams(array $params);

    /**
     * Determine if the profiler has params
     *
     * @return boolean
     */
    public function hasParams();

    /**
     * Get params
     *
     * @return array
     */
    public function getParams();

    /**
     * Add error
     *
     * @param  string $error
     * @param  mixed  $number
     * @return ProfilerInterface
     */
    public function addError($error, $number = null);

    /**
     * Determine if the profiler has errors
     *
     * @return boolean
     */
    public function hasErrors();

    /**
     * Get errors
     *
     * @return array
     */
    public function getErrors();

    /**
     * Finish profiler
     *
     * @return ProfilerInterface
     */
    public function finish();

    /**
     * Get end
     *
     * @return int
     */
    public function getFinish();

    /**
     * Get elapsed time
     *
     * @return int
     */
    public function getElapsed();

}