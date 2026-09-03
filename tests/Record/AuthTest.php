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
            ->varchar('mfa_code', 255)->nullable()
            ->int('mfa_timestamp', 16)->nullable()
            ->primary('id');

        $schema->execute();

        UsersAuth::setDb($this->db);

        $user = new UsersAuth([
            'username' => 'admin',
            'password' => 'admin',
            'attempts' => 0,
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

        // Locked-out accounts stay locked out permanently - not time-based
        $this->assertEquals(4, $user->attempts);
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

    public function testGenerateMfaCodeNoOpsOnUnloadedUser()
    {
        $user   = new UsersAuth();
        $result = $user->generateMfaCode();

        $this->assertInstanceOf(UsersAuth::class, $result);
        $this->assertNull($user->getRawValue('mfa_code'));
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
