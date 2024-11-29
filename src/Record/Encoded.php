<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.0
 */
class Encoded extends \Pop\Db\Record
{

    /**
     * JSON-encoded fields
     * @var array
     */
    protected array $jsonFields = [];

    /**
     * PHP-serialized fields
     * @var array
     */
    protected array $phpFields = [];

    /**
     * Base64-encoded fields
     * @var array
     */
    protected array $base64Fields = [];

    /**
     * Password-hashed fields
     * @var array
     */
    protected array $hashFields = [];

    /**
     * Encrypted fields
     * @var array
     */
    protected array $encryptedFields = [];

    /**
     * Hash algorithm
     * @var string
     */
    protected string $hashAlgorithm = PASSWORD_BCRYPT;

    /**
     * Hash options
     * @var array
     */
    protected array $hashOptions = ['cost' => 10];

    /**
     * Cipher method
     * @var ?string
     */
    protected ?string $cipherMethod = null;

    /**
     * Encrypted field key
     * @var ?string
     */
    protected ?string $key = null;

    /**
     * Encrypted field IV (base64-encoded)
     * @var ?string
     */
    protected ?string $iv = null;

    /**
     * Set all the table column values at once
     *
     * @param  mixed  $columns
     * @throws Exception
     * @return Encoded
     */
    public function setColumns(mixed $columns = null): Encoded
    {
        if ($columns !== null) {
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
     * @throws Exception
     * @return array
     */
    public function toArray(): array
    {
        $result = parent::toArray();

        foreach ($result as $key => $value) {
            if (($this->isEncodedColumn($key)) && ($value !== null)) {
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
    public function encodeValue(string $key, mixed $value): string
    {
        if (in_array($key, $this->jsonFields)) {
            if (!((is_string($value) && (json_decode($value) !== false)) && (json_last_error() == JSON_ERROR_NONE))) {
                $value = json_encode($value);
            }
        } else if (in_array($key, $this->phpFields)) {
            if (!(is_string($value) && (@unserialize($value) !== false))) {
                $value = serialize($value);
            }
        } else if (in_array($key, $this->base64Fields)) {
            if (!(is_string($value) && (base64_encode(base64_decode($value)) === $value))) {
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
            $decodedValue = $this->decodeValue($key, $value);
            if (!(is_string($value) && ($decodedValue !== false) && ($decodedValue != $value))) {
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
    public function decodeValue(string $key, string $value): mixed
    {
        if (in_array($key, $this->jsonFields)) {
            if ($value !== null) {
                $jsonValue = @json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $value = $jsonValue;
                }
            }
        } else if (in_array($key, $this->phpFields)) {
            if ($value !== null) {
                $phpValue = @unserialize($value);
                if ($phpValue !== false) {
                    $value = $phpValue;
                }
            }
        } else if (in_array($key, $this->base64Fields)) {
            if ($value !== null) {
                $base64Value = @base64_decode($value, true);
                if ($base64Value !== false) {
                    $value = $base64Value;
                }
            }
        } else if (in_array($key, $this->encryptedFields)) {
            if (empty($this->cipherMethod) || empty($this->key) || empty($this->iv)) {
                throw new Exception('Error: The encryption properties have not been set for this class.');
            }
            if ($value !== null) {
                $base64Value = @base64_decode($value, true);
                if ($base64Value !== false) {
                    $value = openssl_decrypt(
                        base64_decode($value), $this->cipherMethod, $this->key, OPENSSL_RAW_DATA, base64_decode($this->iv)
                    );
                }
            }
        }

        return $value;
    }

    /**
     * Verify value against hash
     *
     * @param  string $key
     * @param  string $value
     * @return bool
     */
    public function verify(string $key, string $value): bool
    {
        return password_verify($value, $this->{$key});
    }

    /**
     * Scrub the column values and encode them
     *
     * @param  array $columns
     * @throws Exception
     * @return array
     */
    public function encode(array $columns): array
    {
        foreach ($columns as $key => $value) {
            if (($value !== null) && ($this->isEncodedColumn($key))) {
                $columns[$key] = $this->encodeValue($key, $value);
            }
        }

        return $columns;
    }

    /**
     * Scrub the column values and decode them
     *
     * @param  array $columns
     * @throws Exception
     * @return array
     */
    public function decode(array $columns): array
    {
        foreach ($columns as $key => $value) {
            if (($this->isEncodedColumn($key)) && ($value !== null)) {
                $columns[$key] = $this->decodeValue($key, $value);
            }
        }

        return $columns;
    }

    /**
     * Determine if column is an encoded column
     *
     * @param  string $key
     * @return bool
     */
    public function isEncodedColumn(string $key): bool
    {
        return (in_array($key, $this->jsonFields) || in_array($key, $this->phpFields) ||
            in_array($key, $this->base64Fields) || in_array($key, $this->hashFields) || in_array($key, $this->encryptedFields));
    }

    /**
     * Magic method to set the property to the value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @param  mixed  $value
     * @throws Exception
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        if (($value !== null) && ($this->isEncodedColumn($name))) {
            $value = $this->encodeValue($name, $value);
        }
        parent::__set($name, $value);
    }

    /**
     * Magic method to return the value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @throws Exception
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        $value = parent::__get($name);

        if (($this->isEncodedColumn($name)) && ($value !== null)) {
            $value = $this->decodeValue($name, $value);
        }

        return $value;
    }

}
