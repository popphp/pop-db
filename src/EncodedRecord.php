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
namespace Pop\Db;

/**
 * Encoded record class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.4.1
 */
class EncodedRecord extends Record
{

    /**
     * JSON-encoded fields
     * @var array
     */
    protected $jsonFields = [];

    /**
     * PHP-serialized fields
     * @var array
     */
    protected $phpFields = [];

    /**
     * Base64-encoded fields
     * @var array
     */
    protected $base64Fields = [];

    /**
     * Get column values as array
     *
     * @return array
     */
    public function toArray()
    {
        $result = parent::toArray();

        foreach ($result as $key => $value) {
            if ($this->isEncodedColumn($key)) {
                $result[$key] = $this->decodeValue($key, $value);
            }
        }

        return $result;
    }

    /**
     * Magic method to set the property to the value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        if ($this->isEncodedColumn($name)) {
            $value = $this->encodeValue($name, $value);
        }
        parent::__set($name, $value);
    }

    /**
     * Magic method to return the value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        $value = parent::__get($name);

        if ($this->isEncodedColumn($name)) {
            $value = $this->decodeValue($name, $value);
        }

        return $value;
    }

    /**
     * Encode value
     *
     * @param  string $key
     * @param  mixed  $value
     * @return string
     */
    public function encodeValue($key, $value)
    {
        if (in_array($key, $this->jsonFields)) {
            $value = json_encode($value);
        } else if (in_array($key, $this->phpFields)) {
            $value = serialize($value);
        } else if (in_array($key, $this->base64Fields)) {
            $value = base64_encode($value);
        }

        return $value;
    }

    /**
     * Decode value
     *
     * @param  string $key
     * @param  string  $value
     * @return mixed
     */
    public function decodeValue($key, $value)
    {
        if (in_array($key, $this->jsonFields)) {
            $value = json_decode($value, true);
        } else if (in_array($key, $this->phpFields)) {
            $value = unserialize($value);
        } else if (in_array($key, $this->base64Fields)) {
            $value = base64_decode($value);
        }

        return $value;
    }

    /**
     * Determine if column is an encoded column
     *
     * @param  string $key
     * @return boolean
     */
    protected function isEncodedColumn($key)
    {
        return (in_array($key, $this->jsonFields) || in_array($key, $this->phpFields) || in_array($key, $this->base64Fields));
    }

}