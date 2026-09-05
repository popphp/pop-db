<?php

namespace Pop\Db\Test\Record;

use Pop\Db\Db;
use Pop\Db\Record\Auth;
use Pop\Db\Test\TestAsset\UsersAuth;
use Pop\Db\Test\TestAsset\UsersAuthAlphaMfa;
use Pop\Db\Test\TestAsset\UsersAuthWeakHash;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{

    protected $db = null;

    public function setUp(): void
    {
        $this->db = Db::mysqlConnect([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);

        $schema = $this->db->createSchema();

        $schema->dropIfExists('users_auth');
        $schema->execute();

        $schema->create('users_auth')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->int('attempts', 16)->nullable()->defaultIs(0)
            ->int('active', 2)->nullable()->defaultIs(1)
            ->int('verified', 2)->nullable()->defaultIs(1)
            ->int('mfa', 2)->nullable()
            ->int('last_attempt', 16)->nullable()
            ->varchar('mfa_code', 255)->nullable()
            ->int('mfa_timestamp', 16)->nullable()
            ->primary('id');

        $schema->execute();

        UsersAuth::setDb($this->db);

        $user = new UsersAuth([
            'username' => 'admin',
            'password' => 'admin',
            'attempts' => 0,
            'active'   => 1,
            'verified' => 1,
        ]);
        $user->save();
    }

    public function tearDown(): void
    {
        $schema = $this->db->createSchema();
        $schema->dropIfExists('users_auth');
        $schema->execute();
        $this->db->disconnect();
    }

    public function testPasswordFieldIsAutomaticallyHashed()
    {
        // UsersAuth never declares $hashFields, so this exercises the auto-add in Auth::__construct()
        $user = UsersAuth::findOne(['username' => 'admin']);
        $this->assertNotEquals('admin', $user->getRawValue('password'));
        $this->assertTrue($user->verify('password', 'admin'));
    }

    public function testUserExists()
    {
        $user = new UsersAuth();
        $this->assertTrue($user->userExists('admin'));
        $this->assertFalse($user->userExists('nobody'));
    }

    public function testAuthenticateUserDoesNotExist()
    {
        $user = new UsersAuth();
        $this->assertFalse($user->authenticate('nobody', 'admin', false));
        $this->assertTrue($user->hasAuthFailure());
        $this->assertEquals(Auth::USER_DOES_NOT_EXIST, $user->getAuthFailure());
    }

    public function testAuthenticateInvalidCredentialsIncrementsAttempts()
    {
        $user = new UsersAuth();
        $this->assertFalse($user->authenticate('admin', 'bad-password', false));
        $this->assertEquals(Auth::INVALID_CREDENTIALS, $user->getAuthFailure());
        $this->assertEquals(1, $user->attempts);
    }

    public function testAuthenticateAttemptsExceededLocksOutEvenWithCorrectPassword()
    {
        $user = new UsersAuth();
        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);

        $this->assertEquals(3, $user->attempts);

        // Correct password, but attempts have been exceeded (default limit is 3)
        $result = $user->authenticate('admin', 'admin', false);
        $this->assertFalse($result);
        $this->assertEquals(Auth::ATTEMPTS_EXCEEDED, $user->getAuthFailure());

        // Locked-out accounts stay locked out until either resetAttempts() or the
        // (default 900s, far longer than this test takes) lockout expiration elapses
        $this->assertEquals(4, $user->attempts);
    }

    public function testAuthenticateFailsWhenUserNotActiveWithoutIncrementingAttempts()
    {
        $seed = UsersAuth::findOne(['username' => 'admin']);
        $seed->active = 0;
        $seed->save();

        $user   = new UsersAuth();
        $result = $user->authenticate('admin', 'admin', false);

        $this->assertFalse($result);
        $this->assertEquals(Auth::USER_NOT_ACTIVE, $user->getAuthFailure());
        // A hard block, not a guess failure - does not consume the attempts budget
        $this->assertEquals(0, $user->attempts);
    }

    public function testAuthenticateFailsWhenUserNotVerifiedWithoutIncrementingAttempts()
    {
        $seed = UsersAuth::findOne(['username' => 'admin']);
        $seed->verified = 0;
        $seed->save();

        $user   = new UsersAuth();
        $result = $user->authenticate('admin', 'admin', false);

        $this->assertFalse($result);
        $this->assertEquals(Auth::USER_NOT_VERIFIED, $user->getAuthFailure());
        $this->assertEquals(0, $user->attempts);
    }

    public function testAuthenticateUserNotActiveCheckedBeforeUserNotVerified()
    {
        $seed = UsersAuth::findOne(['username' => 'admin']);
        $seed->active   = 0;
        $seed->verified = 0;
        $seed->save();

        $user = new UsersAuth();
        $user->authenticate('admin', 'admin', false);

        $this->assertEquals(Auth::USER_NOT_ACTIVE, $user->getAuthFailure());
    }

    public function testAuthenticateSuccessNoMfaResetsAttemptsAndReturnsTrue()
    {
        $user = new UsersAuth();
        $user->authenticate('admin', 'bad-password', false);
        $this->assertEquals(1, $user->attempts);

        $result = $user->authenticate('admin', 'admin', false);
        $this->assertTrue($result);
        $this->assertFalse($user->hasAuthFailure());
        $this->assertEquals(0, $user->attempts);
    }

    public function testAuthenticateSuccessWithMfaIssuesCodeAndTimestamp()
    {
        $user   = new UsersAuth();
        $result = $user->authenticate('admin', 'admin', true);

        $this->assertInstanceOf(UsersAuth::class, $result);
        $this->assertNotEmpty($result->getRawValue('mfa_code'));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $result->getRawValue('mfa_code'));
        $this->assertGreaterThan(time(), $result->getRawValue('mfa_timestamp'));
    }

    public function testAuthenticateSuccessWithAlphanumericMfaIssuesAlphaCode()
    {
        UsersAuthAlphaMfa::setDb($this->db);
        $user   = new UsersAuthAlphaMfa();
        $result = $user->authenticate('admin', 'admin', true);

        $this->assertInstanceOf(UsersAuthAlphaMfa::class, $result);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $result->getRawValue('mfa_code'));
    }

    public function testAuthenticateMfaFieldOverridesParameterToSkipMfa()
    {
        $seed = UsersAuth::findOne(['username' => 'admin']);
        $seed->mfa = 0;
        $seed->save();

        $user   = new UsersAuth();
        $result = $user->authenticate('admin', 'admin', true);

        $this->assertTrue($result);
        $this->assertNull($user->getRawValue('mfa_code'));
    }

    public function testAuthenticateMfaFieldOverridesParameterToRequireMfa()
    {
        $seed = UsersAuth::findOne(['username' => 'admin']);
        $seed->mfa = 1;
        $seed->save();

        $user   = new UsersAuth();
        $result = $user->authenticate('admin', 'admin', false);

        $this->assertInstanceOf(UsersAuth::class, $result);
        $this->assertNotEmpty($result->getRawValue('mfa_code'));
    }

    public function testAuthenticateMfaFieldUnsetDoesNotOverrideParameter()
    {
        $seed = UsersAuth::findOne(['username' => 'admin']);
        $this->assertNull($seed->mfa);

        // Untouched mfa column (null) must not force $mfa to false
        $result = (new UsersAuth())->authenticate('admin', 'admin', true);
        $this->assertInstanceOf(UsersAuth::class, $result);
        $this->assertNotEmpty($result->getRawValue('mfa_code'));

        $seed->reset('mfa_code', null);
        $seed->reset('mfa_timestamp', null);

        $result = (new UsersAuth())->authenticate('admin', 'admin', false);
        $this->assertTrue($result);
    }

    public function testGetMfaConfigReturnsDefaults()
    {
        $user = new UsersAuth();
        $this->assertEquals([
            'length'              => 6,
            'expires'             => 300,
            'alphanumeric'        => false,
            'mfa_code_field'      => 'mfa_code',
            'mfa_timestamp_field' => 'mfa_timestamp',
        ], $user->getMfaConfig());
    }

    public function testSetMfaConfigIsFluentAndOverridesOnlyGivenKeys()
    {
        $user = new UsersAuth();

        $result = $user->setMfaConfig([
            'length'       => 8,
            'alphanumeric' => true,
        ]);

        $this->assertInstanceOf(UsersAuth::class, $result);

        $mfaConfig = $user->getMfaConfig();
        $this->assertEquals(8, $mfaConfig['length']);
        $this->assertTrue($mfaConfig['alphanumeric']);

        // Untouched keys fall back to the defaults
        $this->assertEquals(300, $mfaConfig['expires']);
        $this->assertEquals('mfa_code', $mfaConfig['mfa_code_field']);
        $this->assertEquals('mfa_timestamp', $mfaConfig['mfa_timestamp_field']);
    }

    public function testSetMfaConfigIgnoresUnknownKeys()
    {
        $user = new UsersAuth();
        $user->setMfaConfig([
            'length'   => 8,
            'unknown'  => 'nope',
            'mfa_code' => 'also-nope',
        ]);

        $mfaConfig = $user->getMfaConfig();
        $this->assertEquals(8, $mfaConfig['length']);
        $this->assertArrayNotHasKey('unknown', $mfaConfig);
        $this->assertArrayNotHasKey('mfa_code', $mfaConfig);
        $this->assertCount(5, $mfaConfig);
    }

    public function testSetMfaConfigOverrideIsAppliedOnAuthenticate()
    {
        $user = new UsersAuth();
        $user->setMfaConfig(['length' => 8, 'alphanumeric' => true]);

        $result = $user->authenticate('admin', 'admin', true);

        $this->assertInstanceOf(UsersAuth::class, $result);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $result->getRawValue('mfa_code'));
    }

    public function testGenerateMfaCodeIsFluentAndPersistsCode()
    {
        $user = UsersAuth::findOne(['username' => 'admin']);
        $this->assertNull($user->getRawValue('mfa_code'));

        $result = $user->generateMfaCode();

        $this->assertInstanceOf(UsersAuth::class, $result);
        $this->assertNotEmpty($result->getRawValue('mfa_code'));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $result->getRawValue('mfa_code'));
        $this->assertGreaterThan(time(), $result->getRawValue('mfa_timestamp'));

        // Confirm it was actually persisted, not just set in-memory
        $reloaded = UsersAuth::findOne(['username' => 'admin']);
        $this->assertEquals($result->getRawValue('mfa_code'), $reloaded->getRawValue('mfa_code'));
    }

    public function testGenerateMfaCodeSetsWasMfaCodeGeneratedAndClearsAuthFailure()
    {
        $user = new UsersAuth();

        // Seed a stale failure from an earlier, unrelated operation
        $user->authenticate('admin', 'bad-password', false);
        $this->assertEquals(Auth::INVALID_CREDENTIALS, $user->getAuthFailure());

        $result = $user->generateMfaCode();

        $this->assertTrue($result->wasMfaCodeGenerated());
        $this->assertFalse($result->hasAuthFailure());
        $this->assertNull($result->getAuthFailure());
    }

    public function testGenerateMfaCodeNoOpsOnUnloadedUser()
    {
        $user   = new UsersAuth();
        $result = $user->generateMfaCode();

        $this->assertInstanceOf(UsersAuth::class, $result);
        $this->assertNull($user->getRawValue('mfa_code'));
        $this->assertFalse($result->wasMfaCodeGenerated());
        $this->assertEquals(Auth::USER_DOES_NOT_EXIST, $result->getAuthFailure());
    }

    public function testGenerateMfaCodeNoOpsWhenAttemptsExceeded()
    {
        $user = new UsersAuth();
        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);

        $this->assertTrue($user->attemptsExceeded());

        // A resend attempt while locked out must not hand out a usable code
        $result = $user->generateMfaCode();

        $this->assertInstanceOf(UsersAuth::class, $result);
        $this->assertNull($user->getRawValue('mfa_code'));
        $this->assertNull($user->getRawValue('mfa_timestamp'));
        $this->assertFalse($result->wasMfaCodeGenerated());
        $this->assertEquals(Auth::ATTEMPTS_EXCEEDED, $result->getAuthFailure());
    }

    public function testGenerateMfaCodeReportsDistinctFailureReasonsForUnloadedVsLockedOut()
    {
        // Regression guard: unloaded user and locked-out user must not be
        // reported as the same failure reason
        $unloadedUser = new UsersAuth();
        $unloadedUser->generateMfaCode();

        $lockedOutUser = new UsersAuth();
        $lockedOutUser->authenticate('admin', 'bad-password', false);
        $lockedOutUser->authenticate('admin', 'bad-password', false);
        $lockedOutUser->authenticate('admin', 'bad-password', false);
        $lockedOutUser->generateMfaCode();

        $this->assertEquals(Auth::USER_DOES_NOT_EXIST, $unloadedUser->getAuthFailure());
        $this->assertEquals(Auth::ATTEMPTS_EXCEEDED, $lockedOutUser->getAuthFailure());
        $this->assertNotEquals($unloadedUser->getAuthFailure(), $lockedOutUser->getAuthFailure());
    }

    public function testGenerateMfaCodeNoOpsWhenUserNotActive()
    {
        $user = UsersAuth::findOne(['username' => 'admin']);
        $user->active = 0;
        $user->save();

        $result = $user->generateMfaCode();

        $this->assertFalse($result->wasMfaCodeGenerated());
        $this->assertEquals(Auth::USER_NOT_ACTIVE, $result->getAuthFailure());
        $this->assertNull($result->getRawValue('mfa_code'));
    }

    public function testAuthenticateMfaSuccess()
    {
        $user = new UsersAuth();
        $user->authenticate('admin', 'admin', true);
        $code = $user->getRawValue('mfa_code');

        $this->assertTrue($user->authenticateMfa($code));
        $this->assertFalse($user->hasAuthFailure());
    }

    public function testAuthenticateMfaSuccessInvalidatesTheCode()
    {
        $user = new UsersAuth();
        $user->authenticate('admin', 'admin', true);
        $code = $user->getRawValue('mfa_code');

        $this->assertTrue($user->authenticateMfa($code));

        // Re-fetch to make sure the invalidation was persisted, not just in-memory
        $user = UsersAuth::findOne(['username' => 'admin']);
        $this->assertNull($user->getRawValue('mfa_code'));
        $this->assertNull($user->getRawValue('mfa_timestamp'));

        // The same code can no longer be used again
        $this->assertFalse($user->authenticateMfa($code));
        $this->assertEquals(Auth::INVALID_MFA_CODE, $user->getAuthFailure());
    }

    public function testAuthenticateMfaWrongCodeIncrementsAttempts()
    {
        $user = new UsersAuth();
        $user->authenticate('admin', 'admin', true);

        $this->assertFalse($user->authenticateMfa('000000'));
        $this->assertEquals(Auth::INVALID_MFA_CODE, $user->getAuthFailure());
        $this->assertEquals(1, $user->attempts);
    }

    public function testAuthenticateMfaExpiredCode()
    {
        $user = new UsersAuth();
        $user->authenticate('admin', 'admin', true);
        $code = $user->getRawValue('mfa_code');

        // Force the stored timestamp into the past
        $user->mfa_timestamp = time() - 10;
        $user->save();

        $this->assertFalse($user->authenticateMfa($code));
        $this->assertEquals(Auth::MFA_CODE_EXPIRED, $user->getAuthFailure());
        $this->assertEquals(1, $user->attempts);
    }

    public function testAuthenticateMfaReusesAttemptsFieldForLockout()
    {
        $user = new UsersAuth();
        $user->authenticate('admin', 'admin', true);
        $code = $user->getRawValue('mfa_code');

        $user->authenticateMfa('000000');
        $user->authenticateMfa('000000');
        $user->authenticateMfa('000000');
        $this->assertEquals(3, $user->attempts);

        // Correct code, but attempts have been exceeded
        $this->assertFalse($user->authenticateMfa($code));
        $this->assertEquals(Auth::ATTEMPTS_EXCEEDED, $user->getAuthFailure());
    }

    public function testAuthenticateMfaUserDoesNotExist()
    {
        $user = new UsersAuth();
        $this->assertFalse($user->authenticateMfa('123456'));
        $this->assertEquals(Auth::USER_DOES_NOT_EXIST, $user->getAuthFailure());
    }

    public function testAuthenticateMfaFailsWhenUserBecomesNotActiveAfterCodeIssued()
    {
        $user = new UsersAuth();
        $user->authenticate('admin', 'admin', true);
        $code = $user->getRawValue('mfa_code');

        // Deactivated after the code was already issued
        $user->active = 0;
        $user->save();

        $this->assertFalse($user->authenticateMfa($code));
        $this->assertEquals(Auth::USER_NOT_ACTIVE, $user->getAuthFailure());
        // A hard block, not a guess failure - does not consume the attempts budget
        $this->assertEquals(0, $user->attempts);
    }

    public function testResetAttemptsIsSafeWhenAlreadyZero()
    {
        $user = UsersAuth::findOne(['username' => 'admin']);
        $this->assertEquals(0, $user->attempts);
        $user->resetAttempts();
        $this->assertEquals(0, $user->attempts);
    }

    public function testSetAttemptsLimitIsFluentAndOverridesDefault()
    {
        $user = new UsersAuth();
        $this->assertEquals(3, $user->getAttemptsLimit());

        $result = $user->setAttemptsLimit(5);
        $this->assertInstanceOf(UsersAuth::class, $result);
        $this->assertEquals(5, $user->getAttemptsLimit());
    }

    public function testHasAttemptsLimitReflectsCurrentValue()
    {
        $user = new UsersAuth();
        $this->assertTrue($user->hasAttemptsLimit());

        $user->setAttemptsLimit(0);
        $this->assertFalse($user->hasAttemptsLimit());
    }

    public function testAttemptsExceededNeverTrueWhenLimitDisabled()
    {
        $user = UsersAuth::findOne(['username' => 'admin']);
        $user->setAttemptsLimit(0);

        // Well past the normal default limit of 3
        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);

        $this->assertEquals(5, $user->attempts);
        $this->assertFalse($user->attemptsExceeded());

        // A correct password still succeeds, since attempts enforcement is disabled
        $result = $user->authenticate('admin', 'admin', false);
        $this->assertTrue($result);
    }

    public function testSetLockoutExpirationIsFluentAndOverridesDefault()
    {
        $user = new UsersAuth();
        $this->assertEquals(900, $user->getLockoutExpiration());

        $result = $user->setLockoutExpiration(60);
        $this->assertInstanceOf(UsersAuth::class, $result);
        $this->assertEquals(60, $user->getLockoutExpiration());
    }

    public function testHasLockoutExpirationReflectsCurrentValue()
    {
        $user = new UsersAuth();
        $this->assertTrue($user->hasLockoutExpiration());

        $user->setLockoutExpiration(0);
        $this->assertFalse($user->hasLockoutExpiration());
    }

    public function testAttemptsExceededStaysTrueBeforeLockoutExpires()
    {
        $user = new UsersAuth();
        $user->setLockoutExpiration(60);

        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);

        // The last guess was just now, well inside the 60s window
        $this->assertTrue($user->attemptsExceeded());
        $this->assertEquals(3, $user->attempts);
    }

    public function testAttemptsExceededAutoClearsOnceLockoutExpires()
    {
        $user = new UsersAuth();
        $user->setLockoutExpiration(60);

        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);

        $this->assertTrue($user->attemptsExceeded());

        // Force the last guess further into the past than the lockout window
        $user->last_attempt = time() - 61;
        $user->save();

        $this->assertFalse($user->attemptsExceeded());
        $this->assertEquals(0, $user->attempts);

        // And a correct password now succeeds, since the lockout auto-cleared
        $result = $user->authenticate('admin', 'admin', false);
        $this->assertTrue($result);
    }

    public function testAttemptsExceededNeverAutoClearsWhenLockoutExpirationDisabled()
    {
        $user = new UsersAuth();
        $user->setLockoutExpiration(0);

        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);

        // Even with a very stale last_attempt, disabled lockout expiration never auto-clears -
        // only an explicit resetAttempts() call can clear it
        $user->last_attempt = time() - 100000;
        $user->save();

        $this->assertTrue($user->attemptsExceeded());
    }

    public function testAlreadyExceededAttemptsDoNotExtendTheLockoutClock()
    {
        $user = new UsersAuth();
        $user->setLockoutExpiration(3600);

        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'bad-password', false);

        $this->assertTrue($user->attemptsExceeded());

        // Pin last_attempt to a known value, still well within the (long) lockout window
        $pinned = time() - 100;
        $user->last_attempt = $pinned;
        $user->save();

        // Further hits while already locked out - even with the correct password - go
        // through the already-exceeded branch, not a fresh guess, so last_attempt must not
        // get bumped back to now(); otherwise repeated hits could keep the lockout open forever
        $user->authenticate('admin', 'bad-password', false);
        $user->authenticate('admin', 'admin', false);

        $this->assertEquals($pinned, $user->last_attempt);
    }

    public function testAuthenticateAttemptsLimitOverrideAppliesAndPersistsOnInstance()
    {
        $user = new UsersAuth();

        // Override to a limit of 1 on this call
        $user->authenticate('admin', 'bad-password', false, 1);
        $this->assertEquals(1, $user->attempts);
        $this->assertEquals(1, $user->getAttemptsLimit());

        // The override is not a one-shot: it sticks on the instance, so the very next
        // call - even with the correct password and no override passed - is locked out
        $result = $user->authenticate('admin', 'admin', false);
        $this->assertFalse($result);
        $this->assertEquals(Auth::ATTEMPTS_EXCEEDED, $user->getAuthFailure());
        $this->assertEquals(2, $user->attempts);
    }

    public function testAuthenticateSuccessRehashesOutdatedPasswordHash()
    {
        // Seed a user whose password was hashed with a deliberately weak/outdated cost
        $weakUser = new UsersAuthWeakHash([
            'username' => 'weakhash',
            'password' => 'admin',
            'attempts' => 0,
        ]);
        $weakUser->save();

        $oldHash = $weakUser->getRawValue('password');

        // Authenticate through the normally-configured class (stronger cost)
        $user   = new UsersAuth();
        $result = $user->authenticate('weakhash', 'admin', false);

        $this->assertTrue($result);

        $reloaded = UsersAuth::findOne(['username' => 'weakhash']);
        $this->assertNotEquals($oldHash, $reloaded->getRawValue('password'));
        $this->assertTrue($reloaded->verify('password', 'admin'));
    }

    public function testGetAuthFailureMessage()
    {
        $user = new UsersAuth();
        $user->authenticate('nobody', 'admin', false);
        $this->assertEquals('The user does not exist', $user->getAuthFailureMessage());
    }

}
