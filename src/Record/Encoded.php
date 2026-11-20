<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
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
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
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
     * JSON-encoded fields and the keys inside each that are encrypted
     * @var array
     */
    protected array $encryptedJsonKeys = [];

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
     * Whether the last verified hash needs to be rehashed
     * @var bool
     */
    protected bool $needsRehash = false;

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
            parent::setColumns($this->encode($this->toColumnsArray($columns)));
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
        $this->checkEncryptedJsonField($key);

        if (in_array($key, $this->jsonFields)) {
            if (!empty($this->encryptedJsonKeys[$key])) {
                if (is_string($value)) {
                    // Only re-encode a JSON string if a key in it gets encrypted, so JSON that is
                    // already stored (e.g. loaded from the database) is kept byte-for-byte
                    $jsonValue = json_decode($value, true);
                    if (is_array($jsonValue)) {
                        $encryptedValue = $this->encryptJsonKeys($key, $jsonValue);
                        if ($encryptedValue !== $jsonValue) {
                            $value = $encryptedValue;
                        }
                    }
                } else if (is_array($value)) {
                    $value = $this->encryptJsonKeys($key, $value);
                }
            }
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
            $encrypter = $this->encrypter();

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
        $this->checkEncryptedJsonField($key);

        if (in_array($key, $this->jsonFields)) {
            $jsonValue = @json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $jsonValue;
            }
            if (is_array($value) && !empty($this->encryptedJsonKeys[$key])) {
                $value = $this->decryptJsonKeys($key, $value);
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
            $encrypter = $this->encrypter();

            if ($this->isEncrypted($value)) {
                $value = $encrypter->decrypt($value);
            }
        }

        return $value;
    }

    /**
     * Get the JSON-encoded fields and the keys inside each that are encrypted
     *
     * @return array
     */
    public function getEncryptedJsonKeys(): array
    {
        return $this->encryptedJsonKeys;
    }

    /**
     * Verify value against hash
     *
     * Also records whether the stored hash needs to be rehashed (outdated algorithm/cost),
     * queryable via needsRehash() and actionable via rehash()
     *
     * @param  string $key
     * @param  string $value
     * @param  bool   $autoRehash
     * @return bool
     */
    public function verify(string $key, string $value, bool $autoRehash = true): bool
    {
        $hasher = Hasher::create($this->hashAlgorithm, $this->hashOptions);
        $hash   = $this->{$key};
        $result = $hasher->verify($value, $hash);

        $this->needsRehash = ($result && $hasher->requiresRehash($hash));

        if (($autoRehash) && ($this->needsRehash)) {
            $this->rehash($key, $value);
        }

        return $result;
    }

    /**
     * Determine if the last verified hash needs to be rehashed
     *
     * @return bool
     */
    public function needsRehash(): bool
    {
        return $this->needsRehash;
    }

    /**
     * Rehash and save the given field with a freshly-hashed value
     *
     * @param  string $key
     * @param  string $value
     * @return void
     */
    public function rehash(string $key, #[\SensitiveParameter] string $value): void
    {
        $this->{$key} = $value;
        $this->save();
        $this->needsRehash = false;
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
            in_array($key, $this->base64Fields) || in_array($key, $this->hashFields) || in_array($key, $this->encryptedFields) ||
            array_key_exists($key, $this->encryptedJsonKeys));
    }

    /**
     * Get the encrypter, loading the encryption properties from $_ENV vars if they are not set
     *
     * @throws Exception
     * @return Encrypter
     */
    protected function encrypter(): Encrypter
    {
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

        return $encrypter;
    }

    /**
     * Determine if a value is a valid encrypted payload
     *
     * @param  string $value
     * @return bool
     */
    protected function isEncrypted(string $value): bool
    {
        if (@base64_decode($value, true) === false) {
            return false;
        }

        $payload = json_decode(base64_decode($value), true);

        return (is_array($payload) && isset($payload['iv']) && isset($payload['value']));
    }

    /**
     * Encrypt the encrypted keys of a JSON field's value that are non-empty strings and not already encrypted
     *
     * @param  string $key
     * @param  array  $value
     * @throws Exception
     * @return array
     */
    protected function encryptJsonKeys(string $key, array $value): array
    {
        $encrypter = null;

        foreach ($this->encryptedJsonKeys[$key] as $name) {
            if (!empty($value[$name]) && is_string($value[$name]) && !$this->isEncrypted($value[$name])) {
                $encrypter    = $encrypter ?? $this->encrypter();
                $value[$name] = $encrypter->encrypt($value[$name]);
            }
        }

        return $value;
    }

    /**
     * Decrypt the encrypted keys of a JSON field's decoded value
     *
     * @param  string $key
     * @param  array  $value
     * @throws Exception
     * @return array
     */
    protected function decryptJsonKeys(string $key, array $value): array
    {
        $encrypter = null;

        foreach ($this->encryptedJsonKeys[$key] as $name) {
            if (!empty($value[$name]) && is_string($value[$name]) && $this->isEncrypted($value[$name])) {
                $encrypter    = $encrypter ?? $this->encrypter();
                $value[$name] = $encrypter->decrypt($value[$name]);
            }
        }

        return $value;
    }

    /**
     * Check that a field with encrypted JSON keys is also a JSON-encoded field
     *
     * @param  string $key
     * @throws Exception
     * @return void
     */
    protected function checkEncryptedJsonField(string $key): void
    {
        if (array_key_exists($key, $this->encryptedJsonKeys) && !in_array($key, $this->jsonFields)) {
            throw new Exception("Error: The encrypted JSON field '" . $key . "' is not a JSON-encoded field.");
        }
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
