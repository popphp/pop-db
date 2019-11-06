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
namespace Pop\Db\Record;

/**
 * Encoded record class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Encoded extends \Pop\Db\Record
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
     * Password-hashed fields
     * @var array
     */
    protected $hashFields = [];

    /**
     * Encrypted fields
     * @var array
     */
    protected $encryptedFields = [];

    /**
     * Hash algorithm
     * @var string
     */
    protected $hashAlgorithm = PASSWORD_BCRYPT;

    /**
     * Hash options ('salt', 'cost')
     * @var array
     */
    protected $hashOptions = [];

    /**
     * Cipher method
     * @var string
     */
    protected $cipherMethod = null;

    /**
     * Encrypted field key
     * @var string
     */
    protected $key = null;

    /**
     * Encrypted field IV (base64-encoded)
     * @var string
     */
    protected $iv = null;

    /**
     * Set all the table column values at once
     *
     * @param  mixed  $columns
     * @throws Exception
     * @return Encoded
     */
    public function setColumns($columns = null)
    {
        if (null !== $columns) {
            if (is_array($columns) || ($columns instanceof \ArrayObject)) {
                $columns = $this->encode($columns);
            } else if ($columns instanceof AbstractRecord) {
                $columns = $this->encode($columns->toArray());
            } else {
                throw new Exception('The parameter passed must be either an array, an array object or null.');
            }

            parent::setColumns($columns);
        }

        return $this;
    }

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
     * Encode value
     *
     * @param  string $key
     * @param  mixed  $value
     * @throws Exception
     * @return string
     */
    public function encodeValue($key, $value)
    {
        if (in_array($key, $this->jsonFields)) {
            if (!(is_string($value) && is_array(@json_decode($value, true)) && (json_last_error() == JSON_ERROR_NONE))) {
                $value = json_encode($value);
            }
        } else if (in_array($key, $this->phpFields)) {
            if (!(is_string($value) && (@unserialize($value) !== false))) {
                $value = serialize($value);
            }
        } else if (in_array($key, $this->base64Fields)) {
            if (!(is_string($value) && (@base64_decode($value, true) !== false))) {
                $value = base64_encode($value);
            }
        } else if (in_array($key, $this->hashFields)) {
            $info = password_get_info($value);
            if (((int)$info['algo'] == 0) || (strtolower($info['algoName']) == 'unknown')) {
                $value = password_hash($value, $this->hashAlgorithm, $this->hashOptions);
            }
        } else if (in_array($key, $this->encryptedFields)) {
            if (empty($this->cipherMethod) || empty($this->key) || empty($this->iv)) {
                throw new Exception('Error: The encryption properties have not been set for this class.');
            }
            if (!(is_string($value) && (@base64_decode($value, true) !== false))) {
                $value = base64_encode(
                    openssl_encrypt($value, $this->cipherMethod, $this->key, OPENSSL_RAW_DATA, base64_decode($this->iv))
                );
            }
        }

        return $value;
    }

    /**
     * Decode value
     *
     * @param  string $key
     * @param  string  $value
     * @throws Exception
     * @return mixed
     */
    public function decodeValue($key, $value)
    {
        if (in_array($key, $this->jsonFields)) {
            $jsonValue = @json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $jsonValue;
            }
        } else if (in_array($key, $this->phpFields)) {
            $phpValue = @unserialize($value);
            if ($phpValue !== false) {
                $value = $phpValue;
            }
        } else if (in_array($key, $this->base64Fields)) {
            $base64Value = @base64_decode($value, true);
            if ($base64Value !== false) {
                $value = $base64Value;
            }
        } else if (in_array($key, $this->encryptedFields)) {
            if (empty($this->cipherMethod) || empty($this->key) || empty($this->iv)) {
                throw new Exception('Error: The encryption properties have not been set for this class.');
            }
            $base64Value = @base64_decode($value, true);
            if ($base64Value !== false) {
                $value = openssl_decrypt(
                    base64_decode($value), $this->cipherMethod, $this->key, OPENSSL_RAW_DATA, base64_decode($this->iv)
                );
            }
        }

        return $value;
    }

    /**
     * Verify value against hash
     *
     * @param  string $key
     * @param  string  $value
     * @return boolean
     */
    public function verify($key, $value)
    {
        return password_verify($value, $this->{$key});
    }

    /**
     * Scrub the column values and encode them
     *
     * @param  array $columns
     * @return array
     */
    public function encode(array $columns)
    {
        foreach ($columns as $key => $value) {
            if ((null !== $value) && ($this->isEncodedColumn($key))) {
                $columns[$key] = $this->encodeValue($key, $value);
            }
        }

        return $columns;
    }

    /**
     * Scrub the column values and decode them
     *
     * @param  array $columns
     * @return array
     */
    public function decode(array $columns)
    {
        foreach ($columns as $key => $value) {
            if ($this->isEncodedColumn($key)) {
                $columns[$key] = $this->decodeValue($key, $value);
            }
        }

        return $columns;
    }

    /**
     * Determine if column is an encoded column
     *
     * @param  string $key
     * @return boolean
     */
    public function isEncodedColumn($key)
    {
        return (in_array($key, $this->jsonFields) || in_array($key, $this->phpFields) ||
            in_array($key, $this->base64Fields) || in_array($key, $this->hashFields) || in_array($key, $this->encryptedFields));
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
        if ((null !== $value) && ($this->isEncodedColumn($name))) {
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

}