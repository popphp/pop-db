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
    const string USER_NOT_ACTIVE     = 'USER_NOT_ACTIVE';
    const string USER_NOT_VERIFIED   = 'USER_NOT_VERIFIED';
    const string ATTEMPTS_EXCEEDED   = 'ATTEMPTS_EXCEEDED';
    const string INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
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
     * Active field
     * @var ?string
     */
    protected ?string $activeField = 'active';

    /**
     * Verified field
     * @var ?string
     */
    protected ?string $verifiedField = 'verified';

    /**
     * Last attempt timestamp field
     * @var ?string
     */
    protected ?string $lastAttemptField = 'last_attempt';

    /**
     * MFA flag field
     * @var ?string
     */
    protected ?string $mfaField = 'mfa';

    /**
     * Attempts limit - set to zero to skip attempts enforcement
     * @var int
     */
    protected int $attemptsLimit = 3;

    /**
     * Lockout expiration (in seconds) - set to zero to never expire (admin would have to manually reset the attempts)
     * @var int
     */
    protected int $lockoutExpiration = 900; // 15 minute default

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
     * Flag if MFA code was generated
     * @var bool
     */
    protected bool $mfaCodeGenerated = false;

    /**
     * Auth failure messages
     * @var array
     */
    protected array $authFailureMessages = [
        self::USER_DOES_NOT_EXIST => 'The user does not exist',
        self::USER_NOT_ACTIVE     => 'The user is not active',
        self::USER_NOT_VERIFIED   => 'The user is not verified',
        self::ATTEMPTS_EXCEEDED   => 'The authentication attempts have been exceeded',
        self::INVALID_CREDENTIALS => 'Invalid credentials',
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
     * Set MFA config
     *
     * @param  array $mfaConfig
     * @return static
     */
    public function setMfaConfig(array $mfaConfig): static
    {
        $this->mfaConfig = array_merge($this->mfaConfig, array_intersect_key($mfaConfig, $this->mfaConfig));
        return $this;
    }

    /**
     * Get MFA config
     *
     * @return array
     */
    public function getMfaConfig(): array
    {
        return $this->mfaConfig;
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
     * Set lockout expiration
     *
     * @param  int $lockoutExpiration
     * @return static
     */
    public function setLockoutExpiration(int $lockoutExpiration): static
    {
        $this->lockoutExpiration = $lockoutExpiration;
        return $this;
    }

    /**
     * Get lockout expiration
     *
     * @return int
     */
    public function getLockoutExpiration(): int
    {
        return $this->lockoutExpiration;
    }

    /**
     * Has lockout expiration
     *
     * @return bool
     */
    public function hasLockoutExpiration(): bool
    {
        return ($this->lockoutExpiration > 0);
    }

    /**
     * Lockout has expired
     *
     * True once $lockoutExpiration seconds have passed since $lastAttemptField was last set -
     * always false if lockout expiration or $lastAttemptField tracking is disabled, in which
     * case an exceeded lockout can only be cleared with an explicit resetAttempts() call
     *
     * @return bool
     */
    public function lockoutExpired(): bool
    {
        return (
            $this->hasLockoutExpiration() && !empty($this->lastAttemptField) && $this->userExists() &&
            isset($this->{$this->lastAttemptField}) &&
            (time() >= ((int)$this->{$this->lastAttemptField} + $this->lockoutExpiration))
        );
    }

    /**
     * Attempts exceeded
     *
     * Auto-clears (resets attempts, returns false) once the lockout has expired
     *
     * @return bool
     */
    public function attemptsExceeded(): bool
    {
        if ((!$this->userExists()) || (!$this->hasAttemptsLimit()) || ((int)$this->{$this->attemptsField} < $this->attemptsLimit)) {
            return false;
        }

        if ($this->lockoutExpired()) {
            $this->resetAttempts();
            return false;
        }

        return true;
    }

    /**
     * Is user active
     *
     * @return bool
     */
    public function userActive(): bool
    {
        return (empty($this->activeField) || (($this->userExists()) && ($this->{$this->activeField})));
    }

    /**
     * Is user verified
     *
     * @return bool
     */
    public function userVerified(): bool
    {
        return (empty($this->verifiedField) || (($this->userExists()) && ($this->{$this->verifiedField})));
    }

    /**
     * Resolve whether MFA should be enforced for this authentication attempt
     *
     * If $mfaField is configured and actually set on the user record, it overrides $mfa in
     * either direction; otherwise (no field configured, or the column is null/unset - e.g. a
     * not-yet-migrated row) $mfa is returned untouched, so a missing value can never silently
     * disable MFA
     *
     * @param  bool $mfa
     * @return bool
     */
    protected function resolveMfa(bool $mfa): bool
    {
        return (!empty($this->mfaField) && isset($this->{$this->mfaField})) ?
            (bool)$this->{$this->mfaField} : $mfa;
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
     * Record a failed (guess-type) attempt: increment the attempts field and, if
     * $lastAttemptField is configured, stamp it with now() to (re)anchor the lockout clock
     *
     * Only called for actual credential/code guesses - not for attempts already blocked by
     * attemptsExceeded(), so repeatedly hitting an already-locked-out account cannot keep
     * pushing the lockout expiration further into the future
     *
     * @return void
     */
    protected function recordFailedAttempt(): void
    {
        $this->{$this->attemptsField} = (int)$this->{$this->attemptsField} + 1;
        if (!empty($this->lastAttemptField)) {
            $this->{$this->lastAttemptField} = time();
        }
        $this->save();
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
     *       -> If the user was not found, not active, or not verified, it returns false without
     *          incrementing the attempts field - these are hard blocks, not guess failures
     *       -> If the attempts have been exceeded, or the credentials themselves were wrong,
     *          the attempts field is incremented, then it returns false - unless the lockout
     *          has since expired (see $lockoutExpiration), in which case attempts are reset
     *          first and the check falls through to the credentials themselves
     *   - Else, if auth is successful:
     *       -> If the stored password hash was made with an outdated algorithm/cost, it is
     *          transparently rehashed and saved using the just-verified plaintext password
     *       -> If $mfaField is configured and set on the user record, it overrides $mfa in
     *          either direction (see resolveMfa()) - otherwise $mfa is used as passed in
     *       -> If MFA applies, the user record is updated with a fresh MFA code and timestamp
     *          from which the calling app can deploy the MFA notification
     *       -> The user record is then returned
     *
     * @param  string $attemptedUsername
     * @param  string $attemptedPassword
     * @param  bool   $mfa               default, overridable per-user via $mfaField
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
        // If user is not active
        } else if ((!$this->userActive())) {
            $this->authFailure = self::USER_NOT_ACTIVE;
            return false;
        // If user is not verified
        } else if ((!$this->userVerified())) {
            $this->authFailure = self::USER_NOT_VERIFIED;
            return false;
        // If attempts exceeded
        } else if (($this->attemptsExceeded())) {
            $this->authFailure = self::ATTEMPTS_EXCEEDED;
            $this->increment($this->attemptsField);
            return false;
        // If auth fails
        } else if (!$this->verify($this->passwordField, $attemptedPassword)) {
            $this->authFailure = self::INVALID_CREDENTIALS;
            $this->recordFailedAttempt();
            return false;
        } else {
            // Upon success, reset attempts field (the password hash, if outdated, was
            // already transparently rehashed by verify() above)
            $this->resetAttempts();
            $this->authFailure = null;
            $mfa               = $this->resolveMfa($mfa);

            // If not MFA, return true
            if (!$mfa) {
                return true;
            } else {
                $this->generateMfaCode();
                return $this;
            }
        }
    }

    /**
     * Authenticate MFA code
     *
     *   - The user records needs to be pre-fetched and loaded into this instance
     *   - Not active/not verified are rechecked here too, same as authenticate() - a hard
     *     block, so neither increments the attempts field
     *   - Wrong or expired code guesses count against the same $attemptsField/$attemptsLimit
     *     as login attempts, so a locked-out user is also locked out of MFA guessing
     *   - A lockout that has since expired (see $lockoutExpiration) is auto-cleared here too,
     *     the same as in authenticate()
     *   - On success, the stored code and timestamp are cleared so the code cannot be reused
     *
     * @param  string $mfaCode
     * @return bool
     */
    public function authenticateMfa(string $mfaCode): bool
    {
        if (!$this->userExists()) {
            $this->authFailure = self::USER_DOES_NOT_EXIST;
        } else if (!$this->userActive()) {
            $this->authFailure = self::USER_NOT_ACTIVE;
        } else if (!$this->userVerified()) {
            $this->authFailure = self::USER_NOT_VERIFIED;
        } else if ($this->attemptsExceeded()) {
            $this->authFailure = self::ATTEMPTS_EXCEEDED;
            $this->increment($this->attemptsField);
        } else if (!$this->verifyMfaCode($mfaCode)) {
            $this->authFailure = self::INVALID_MFA_CODE;
            $this->recordFailedAttempt();
        } else if (time() > (int)$this->{$this->mfaConfig['mfa_timestamp_field']}) {
            $this->authFailure = self::MFA_CODE_EXPIRED;
            $this->recordFailedAttempt();
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
     * Generate (or regenerate/resend) an MFA code, expiration timestamp, and persist them
     *
     * No-ops on an unloaded, not active, or not verified user, or once attempts have been
     * exceeded - a locked-out account cannot be handed a fresh, usable code via resend; it
     * must go through resetAttempts() first
     *
     * @return static
     */
    public function generateMfaCode(): static
    {
        if (!$this->userExists()) {
            $this->mfaCodeGenerated = false;
            $this->authFailure      = self::USER_DOES_NOT_EXIST;
        } else if (!$this->userActive()) {
            $this->mfaCodeGenerated = false;
            $this->authFailure      = self::USER_NOT_ACTIVE;
        } else if (!$this->userVerified()) {
            $this->mfaCodeGenerated = false;
            $this->authFailure      = self::USER_NOT_VERIFIED;
        } else if ($this->attemptsExceeded()) {
            $this->mfaCodeGenerated = false;
            $this->authFailure      = self::ATTEMPTS_EXCEEDED;
        } else {
            $this->{$this->mfaConfig['mfa_timestamp_field']} = time() + $this->mfaConfig['expires'];
            $this->{$this->mfaConfig['mfa_code_field']}      = ($this->mfaConfig['alphanumeric']) ?
                Str::createRandomAlphaNum($this->mfaConfig['length'], Str::UPPERCASE) :
                Str::createRandomNumeric($this->mfaConfig['length']);

            $this->save();

            $this->authFailure      = null;
            $this->mfaCodeGenerated = true;
        }

        return $this;
    }

    /**
     * Get MFA code generated flag
     *
     * @return bool
     */
    public function wasMfaCodeGenerated(): bool
    {
        return $this->mfaCodeGenerated;
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
