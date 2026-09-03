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

use Pop\Utils\Str;

/**
 * User authentication record class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
class Auth extends Encoded
{

    /**
     * Auth constants
     */
    const string USER_DOES_NOT_EXIST = 'USER_DOES_NOT_EXIST';
    const string INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    const string ATTEMPTS_EXCEEDED   = 'ATTEMPTS_EXCEEDED';
    const string INVALID_MFA_CODE    = 'INVALID_MFA_CODE';
    const string MFA_CODE_EXPIRED    = 'MFA_CODE_EXPIRED';

    /**
     * Username field
     * @var string
     */
    protected string $usernameField = 'username';

    /**
     * Password field
     * @var string
     */
    protected string $passwordField = 'password';

    /**
     * Attempts field
     * @var string
     */
    protected string $attemptsField = 'attempts';

    /**
     * Attempts limit - set to zero to skip attempts enforcement
     * @var int
     */
    protected int $attemptsLimit = 3;

    /**
     * MFA config
     * @var array
     */
    protected array $mfaConfig = [
        'length'              => 6,              // Code length
        'expires'             => 300,            // Seconds
        'alphanumeric'        => false,          // Numeric by default, can be alphanumeric
        'mfa_code_field'      => 'mfa_code',     // varchar database column, nullable
        'mfa_timestamp_field' => 'mfa_timestamp' // integer database column, nullable
    ];

    /**
     * Auth failure
     * @var ?string
     */
    protected ?string $authFailure = null;

    /**
     * Auth failure messages
     * @var array
     */
    protected array $authFailureMessages = [
        self::USER_DOES_NOT_EXIST => 'The user does not exist',
        self::INVALID_CREDENTIALS => 'Invalid credentials',
        self::ATTEMPTS_EXCEEDED   => 'The authentication attempts have been exceeded',
        self::INVALID_MFA_CODE    => 'Invalid MFA code',
        self::MFA_CODE_EXPIRED    => 'MFA code has expired',
    ];

    /**
     * Constructor
     *
     * Instantiate the Auth record object and ensure the password field is always hashed,
     * regardless of whether the child class remembers to declare it in $hashFields
     *
     * @param  mixed ...$args
     */
    public function __construct(mixed ...$args)
    {
        if (!in_array($this->passwordField, $this->hashFields, true)) {
            $this->hashFields[] = $this->passwordField;
        }

        parent::__construct(...$args);
    }

    /**
     * Does user exist
     *
     * @param  ?string $attemptedUsername
     * @return bool
     */
    public function userExists(?string $attemptedUsername = null): bool
    {
        // Check an attempted username value directly
        if ($attemptedUsername !== null) {
            return isset(static::findOne([$this->usernameField => $attemptedUsername])->id);
        // Else, check if this instance is loaded with a valid user
        } else {
            return isset($this->{$this->usernameField});
        }
    }

    /**
     * Set attempts limit
     *
     * @param  int $attemptsLimit
     * @return static
     */
    public function setAttemptsLimit(int $attemptsLimit): static
    {
        $this->attemptsLimit = $attemptsLimit;
        return $this;
    }

    /**
     * Get attempts limit
     *
     * @return int
     */
    public function getAttemptsLimit(): int
    {
        return $this->attemptsLimit;
    }

    /**
     * Has attempts limit
     *
     * @return bool
     */
    public function hasAttemptsLimit(): bool
    {
        return ($this->attemptsLimit > 0);
    }

    /**
     * Attempts exceeded
     *
     * @return bool
     */
    public function attemptsExceeded(): bool
    {
        return (($this->userExists()) && ($this->hasAttemptsLimit()) && ((int)$this->{$this->attemptsField} >= $this->attemptsLimit));
    }

    /**
     * Reset attempts
     *
     * @return static
     */
    public function resetAttempts(): static
    {
        if (($this->userExists()) && ((int)$this->{$this->attemptsField} !== 0)) {
            $this->reset($this->attemptsField, 0);
        }

        return $this;
    }

    /**
     * Get user
     *
     * @param  string $attemptedUsername
     * @return static
     */
    public function getUser(string $attemptedUsername): static
    {
        return $this->getOne([$this->usernameField => $attemptedUsername]);
    }

    /**
     * Authenticate user attempt
     *
     *   - If auth is unsuccessful:
     *       -> If the user was not found, it returns false
     *       -> If user was found, but auth did not pass or the attempts have been exceeded,
     *          the attempts field is incremented, then it returns false
     *   - Else, if auth is successful:
     *       -> If the stored password hash was made with an outdated algorithm/cost, it is
     *          transparently rehashed and saved using the just-verified plaintext password
     *       -> If $mfa = true, the user record is updated with a fresh MFA code and timestamp
     *          from which the calling app can deploy the MFA notification
     *       -> The user record is then returned
     *
     * @param  string $attemptedUsername
     * @param  string $attemptedPassword
     * @param  bool   $mfa
     * @param  ?int   $attemptsLimit
     * @return bool|static
     */
    public function authenticate(
        string $attemptedUsername, string $attemptedPassword, bool $mfa = true, ?int $attemptsLimit = null
    ): bool|static
    {
        if ($attemptsLimit !== null) {
            $this->setAttemptsLimit($attemptsLimit);
        }

        $this->getUser($attemptedUsername);

        // If user doesn't exist
        if (!$this->userExists()) {
            $this->authFailure = self::USER_DOES_NOT_EXIST;
            return false;
        // If attempts exceeded
        } else if (($this->attemptsExceeded())) {
            $this->authFailure = self::ATTEMPTS_EXCEEDED;
            $this->increment($this->attemptsField);
            return false;
        // If auth fails
        } else if (!$this->verify($this->passwordField, $attemptedPassword)) {
            $this->authFailure = self::INVALID_CREDENTIALS;
            $this->increment($this->attemptsField);
            return false;
        } else {
            // Upon success, reset attempts field (the password hash, if outdated, was
            // already transparently rehashed by verify() above)
            $this->resetAttempts();
            $this->authFailure = null;

            // If not MFA, return true
            if (!$mfa) {
                return true;
            } else {
                $this->{$this->mfaConfig['mfa_timestamp_field']} = time() + $this->mfaConfig['expires'];
                $this->{$this->mfaConfig['mfa_code_field']}      = ($this->mfaConfig['alphanumeric']) ?
                    Str::createRandomAlphaNum($this->mfaConfig['length'], Str::UPPERCASE) :
                    Str::createRandomNumeric($this->mfaConfig['length']);

                $this->save();

                return $this;
            }
        }
    }

    /**
     * Authenticate MFA code
     *
     *   - The user records needs to be pre-fetched and loaded into this instance
     *   - Wrong or expired code guesses count against the same $attemptsField/$attemptsLimit
     *     as login attempts, so a locked-out user is also locked out of MFA guessing
     *   - On success, the stored code and timestamp are cleared so the code cannot be reused
     *
     * @param  string $mfaCode
     * @return bool
     */
    public function authenticateMfa(string $mfaCode): bool
    {
        if (!$this->userExists()) {
            $this->authFailure = self::USER_DOES_NOT_EXIST;
        } else if ($this->attemptsExceeded()) {
            $this->authFailure = self::ATTEMPTS_EXCEEDED;
            $this->increment($this->attemptsField);
        } else if (!$this->verifyMfaCode($mfaCode)) {
            $this->authFailure = self::INVALID_MFA_CODE;
            $this->increment($this->attemptsField);
        } else if (time() > (int)$this->{$this->mfaConfig['mfa_timestamp_field']}) {
            $this->authFailure = self::MFA_CODE_EXPIRED;
            $this->increment($this->attemptsField);
        } else {
            $this->authFailure = null;
            $this->{$this->attemptsField}                    = 0;
            $this->{$this->mfaConfig['mfa_code_field']}      = null;
            $this->{$this->mfaConfig['mfa_timestamp_field']} = null;
            $this->save();
        }

        return (!$this->hasAuthFailure());
    }

    /**
     * Verify the attempted MFA code against the stored code using a timing-safe comparison
     *
     * @param  string $attemptedCode
     * @return bool
     */
    protected function verifyMfaCode(string $attemptedCode): bool
    {
        $storedCode = $this->{$this->mfaConfig['mfa_code_field']};
        return (is_string($storedCode) && ($storedCode !== '') && hash_equals($storedCode, $attemptedCode));
    }


    /**
     * Has auth failure
     *
     * @return bool
     */
    public function hasAuthFailure(): bool
    {
        return ($this->authFailure !== null);
    }

    /**
     * Get auth failure
     *
     * @return ?string
     */
    public function getAuthFailure(): ?string
    {
        return $this->authFailure;
    }

    /**
     * Get auth failure message
     *
     * @return ?string
     */
    public function getAuthFailureMessage(): ?string
    {
        return ($this->hasAuthFailure() && array_key_exists($this->authFailure, $this->authFailureMessages)) ?
            $this->authFailureMessages[$this->authFailure] : null;
    }

}
