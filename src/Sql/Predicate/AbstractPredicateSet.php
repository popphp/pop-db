<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Predicate;

/**
 * Abstract predicate set class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
abstract class AbstractPredicateSet
{

    /**
     * Format
     * @var string
     */
    protected $format = null;

    /**
     * Values
     * @var array
     */
    protected $values = null;

    /**
     * Combine
     * @var string
     */
    protected $combine = 'AND';

    /**
     * Constructor
     *
     * Instantiate the predicate set object
     *
     * @param  array  $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * Get the format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Get the values
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Get the combine
     *
     * @return string
     */
    public function getCombine()
    {
        return $this->combine;
    }

    /**
     * Get the combine
     *
     * @param  string $combine
     * @return AbstractPredicateSet
     */
    public function setCombine($combine)
    {
        $this->combine = $combine;
        return $this;
    }

}