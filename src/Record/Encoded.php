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
namespace Pop\Db\Record;

use Pop\Crypt\Hashing\Hasher;
use Pop\Crypt\Encryption\Encrypter;

/**
 * Encoded record class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.7.0
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
    protected array $hashOptions = [];

    /**
     * Encryption cipher method
     * @var ?string
     */
    protected ?string $cipherMethod = null;

    /**
     * Encryption key
     * @var ?string
     */
    protected ?string $key = null;

    /**
     * Encryption previous keys
     * @var array
     */
    protected array $previousKeys = [];

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
            $hasher = Hasher::create($this->hashAlgorithm, $this->hashOptions);
            $info   = $hasher->getInfo($value);
            if (((int)$info['algo'] == 0) || (strtolower($info['algoName']) == 'unknown')) {
                $value = $hasher->make($value);
            }
        } else if (in_array($key, $this->encryptedFields)) {
            // Attempt to load encryption properties from $_ENV
            if (empty($this->cipherMethod) || empty($this->key)) {
                $this->loadEncryptionProperties();
            }
            if (empty($this->cipherMethod) || empty($this->key)) {
                throw new Exception('Error: The encryption properties have not been set.');
            }

            $encrypter = new Encrypter($this->key, $this->cipherMethod, false);

            // Load any previous encryption keys
            if (!empty($this->previousKeys)) {
                $encrypter->setPreviousKeys($this->previousKeys, false);
            }

            $decodedValue = $this->decodeValue($key, $value);
            if (!(is_string($value) && ($decodedValue !== false) && ($decodedValue != $value))) {
                $value = $encrypter->encrypt($value);
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
            // Attempt to load encryption properties from $_ENV
            if (empty($this->cipherMethod) || empty($this->key)) {
                $this->loadEncryptionProperties();
            }
            if (empty($this->cipherMethod) || empty($this->key)) {
                throw new Exception('Error: The encryption properties have not been set.');
            }

            $encrypter = new Encrypter($this->key, $this->cipherMethod, false);

            // Load any previous encryption keys
            if (!empty($this->previousKeys)) {
                $encrypter->setPreviousKeys($this->previousKeys, false);
            }

            if ($value !== null) {
                $base64Value = @base64_decode($value, true);
                if ($base64Value !== false) {
                    $value = $encrypter->decrypt($value);
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
        $hasher = Hasher::create($this->hashAlgorithm, $this->hashOptions);
        return $hasher->verify($value, $this->{$key});
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
     * Attempt to load encryption properties from $_ENV vars
     *
     * @return void
     */
    public function loadEncryptionProperties(): void
    {
        if (empty($this->cipherMethod) && !empty($_ENV['APP_CIPHER_METHOD'])) {
            $this->cipherMethod = trim($_ENV['APP_CIPHER_METHOD']);
        }
        if (empty($this->key) && !empty($_ENV['APP_KEY'])) {
            $this->key = trim($_ENV['APP_KEY']);
            if (!empty($_ENV['APP_PREVIOUS_KEYS'])) {
                $this->previousKeys = array_map('trim', explode(',', $_ENV['APP_PREVIOUS_KEYS']));
            }
        }
    }

    /**
     * Get raw un-encoded value
     *
     * @param  string $name
     * @return mixed
     */
    public function getRawValue(string $name): mixed
    {
        return parent::__get($name);
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
