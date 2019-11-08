<?php

namespace Pop\Db\Test\Sql\Parser;

use Pop\Db\Sql\Parser\Expression;
use PHPUnit\Framework\TestCase;

class ExpressionTest extends TestCase
{

    public function testParse()
    {
        $expressions = [
            "username = 'admin'",
            "email = 'test@test.com'",
            "attempts >= 5",
            "role IS NULL",
            "title LIKE 'CEO%'",
            "id IN (1, 2, 3)",
            "logins BETWEEN 50 AND 100"
        ];

        $components = Expression::parseExpressions($expressions);

        $this->assertEquals(7, count($components));

        $this->assertEquals(3, count($components[0]));
        $this->assertTrue(isset($components[0]['column']));
        $this->assertTrue(isset($components[0]['operator']));
        $this->assertTrue(isset($components[0]['value']));
        $this->assertEquals('username', $components[0]['column']);
        $this->assertEquals('=', $components[0]['operator']);
        $this->assertEquals('admin', $components[0]['value']);

        $this->assertEquals(3, count($components[1]));
        $this->assertTrue(isset($components[1]['column']));
        $this->assertTrue(isset($components[1]['operator']));
        $this->assertTrue(isset($components[1]['value']));
        $this->assertEquals('email', $components[1]['column']);
        $this->assertEquals('=', $components[1]['operator']);
        $this->assertEquals('test@test.com', $components[1]['value']);

        $this->assertEquals(3, count($components[2]));
        $this->assertTrue(isset($components[2]['column']));
        $this->assertTrue(isset($components[2]['operator']));
        $this->assertTrue(isset($components[2]['value']));
        $this->assertEquals('attempts', $components[2]['column']);
        $this->assertEquals('>=', $components[2]['operator']);
        $this->assertEquals('5', $components[2]['value']);

        $this->assertEquals(3, count($components[3]));
        $this->assertTrue(isset($components[3]['column']));
        $this->assertTrue(isset($components[3]['operator']));
        $this->assertEquals('role', $components[3]['column']);
        $this->assertEquals('IS NULL', $components[3]['operator']);
        $this->assertNull($components[3]['value']);

        $this->assertEquals(3, count($components[4]));
        $this->assertTrue(isset($components[4]['column']));
        $this->assertTrue(isset($components[4]['operator']));
        $this->assertTrue(isset($components[4]['value']));
        $this->assertEquals('title', $components[4]['column']);
        $this->assertEquals('LIKE', $components[4]['operator']);
        $this->assertEquals('CEO%', $components[4]['value']);

        $this->assertEquals(3, count($components[5]));
        $this->assertTrue(isset($components[5]['column']));
        $this->assertTrue(isset($components[5]['operator']));
        $this->assertTrue(isset($components[5]['value']));
        $this->assertEquals('id', $components[5]['column']);
        $this->assertEquals('IN', $components[5]['operator']);
        $this->assertEquals(3, count($components[5]['value']));
        $this->assertEquals('1', $components[5]['value'][0]);
        $this->assertEquals('2', $components[5]['value'][1]);
        $this->assertEquals('3', $components[5]['value'][2]);

        $this->assertEquals(3, count($components[6]));
        $this->assertTrue(isset($components[6]['column']));
        $this->assertTrue(isset($components[6]['operator']));
        $this->assertTrue(isset($components[6]['value']));
        $this->assertEquals('logins', $components[6]['column']);
        $this->assertEquals('BETWEEN', $components[6]['operator']);
        $this->assertEquals(2, count($components[6]['value']));
        $this->assertEquals('50', $components[6]['value'][0]);
        $this->assertEquals('100', $components[6]['value'][1]);
    }

    public function testParseException()
    {
        $this->expectException('Pop\Db\Sql\Parser\Exception');
        $component = Expression::parse("username <> 'admin'");
    }

    public function testConvertExpressionsToShorthand()
    {
        $expressions = [
            "username = 'admin'",
            "email = 'test@test.com'",
            "attempts >= 5",
            "role IS NULL",
            "profile IS NOT NULL",
            "title LIKE '%CEO%'",
            "position NOT LIKE '%Staff%'",
            "id IN (1, 2, 3)",
            "logins BETWEEN 50 AND 100"
        ];

        $columns = Expression::convertExpressionsToShorthand($expressions);

        $this->assertTrue(isset($columns['username']));
        $this->assertTrue(isset($columns['email']));
        $this->assertTrue(isset($columns['attempts>=']));
        $this->assertTrue(array_key_exists('role', $columns));
        $this->assertTrue(array_key_exists('profile-', $columns));
        $this->assertTrue(isset($columns['%title%']));
        $this->assertTrue(isset($columns['-%position%-']));
        $this->assertTrue(isset($columns['id']));
        $this->assertTrue(isset($columns['logins']));

        $this->assertEquals('admin', $columns['username']);
        $this->assertEquals('test@test.com', $columns['email']);
        $this->assertEquals('5', $columns['attempts>=']);
        $this->assertNull($columns['role']);
        $this->assertNull($columns['profile-']);
        $this->assertEquals('CEO', $columns['%title%']);
        $this->assertEquals('Staff', $columns['-%position%-']);
        $this->assertTrue(is_array($columns['id']));
        $this->assertEquals(3, count($columns['id']));
        $this->assertEquals(1, $columns['id'][0]);
        $this->assertEquals(2, $columns['id'][1]);
        $this->assertEquals(3, $columns['id'][2]);
        $this->assertEquals('(50, 100)', $columns['logins']);
    }

    public function testParseShorthand()
    {
        $columns = [
            'username'     => 'admin',
            'email'        => 'test@test.com',
            'attempts>='   => '5',
            'role'         => null,
            'profile-'     => null,
            '%title%'      => 'CEO',
            '-%position%-' => 'Staff',
            'id'           => [1, 2, 3],
            'logins'       => '(50, 100)',
        ];

        $parsed = Expression::parseShorthand($columns);

        $this->assertTrue(isset($parsed['expressions']));
        $this->assertTrue(isset($parsed['params']));
        $this->assertEquals(9, count($parsed['expressions']));
        $this->assertEquals("username = 'admin'", $parsed['expressions'][0]);
        $this->assertEquals("email = 'test@test.com'", $parsed['expressions'][1]);
        $this->assertEquals("attempts >= 5", $parsed['expressions'][2]);
        $this->assertEquals("role IS NULL", $parsed['expressions'][3]);
        $this->assertEquals("profile IS NOT NULL", $parsed['expressions'][4]);
        $this->assertEquals("title LIKE '%CEO%'", $parsed['expressions'][5]);
        $this->assertEquals("position NOT LIKE '%Staff%'", $parsed['expressions'][6]);
        $this->assertEquals("id IN (1, 2, 3)", $parsed['expressions'][7]);
        $this->assertEquals("logins BETWEEN 50 AND 100", $parsed['expressions'][8]);

        $this->assertEquals(10, count($parsed['params']));
        $this->assertEquals('admin', $parsed['params'][0]);
        $this->assertEquals('test@test.com', $parsed['params'][1]);
        $this->assertEquals('5', $parsed['params'][2]);
        $this->assertEquals('%CEO%', $parsed['params'][3]);
        $this->assertEquals('%Staff%', $parsed['params'][4]);
        $this->assertEquals(1, $parsed['params'][5]);
        $this->assertEquals(2, $parsed['params'][6]);
        $this->assertEquals(3, $parsed['params'][7]);
        $this->assertEquals('50', $parsed['params'][8]);
        $this->assertEquals('100', $parsed['params'][9]);
    }

    public function testParseShorthandWithPlaceholder1()
    {
        $columns = [
            'username'     => 'admin',
            'email'        => 'test@test.com',
            'attempts>='   => '5',
            'role'         => null,
            'profile-'     => null,
            '%title%'      => 'CEO',
            '-%position%-' => 'Staff',
            'id'           => [1, 2, 3],
            'logins'       => '(50, 100)',
        ];

        $parsed = Expression::parseShorthand($columns, '?');

        $this->assertTrue(isset($parsed['expressions']));
        $this->assertTrue(isset($parsed['params']));
        $this->assertEquals(9, count($parsed['expressions']));
        $this->assertEquals("username = ?", $parsed['expressions'][0]);
        $this->assertEquals("email = ?", $parsed['expressions'][1]);
        $this->assertEquals("attempts >= ?", $parsed['expressions'][2]);
        $this->assertEquals("role IS NULL", $parsed['expressions'][3]);
        $this->assertEquals("profile IS NOT NULL", $parsed['expressions'][4]);
        $this->assertEquals("title LIKE ?", $parsed['expressions'][5]);
        $this->assertEquals("position NOT LIKE ?", $parsed['expressions'][6]);
        $this->assertEquals("id IN (?, ?, ?)", $parsed['expressions'][7]);
        $this->assertEquals("logins BETWEEN ? AND ?", $parsed['expressions'][8]);

        $this->assertEquals(10, count($parsed['params']));
        $this->assertEquals('admin', $parsed['params'][0]);
        $this->assertEquals('test@test.com', $parsed['params'][1]);
        $this->assertEquals('5', $parsed['params'][2]);
        $this->assertEquals('%CEO%', $parsed['params'][3]);
        $this->assertEquals('%Staff%', $parsed['params'][4]);
        $this->assertEquals(1, $parsed['params'][5]);
        $this->assertEquals(2, $parsed['params'][6]);
        $this->assertEquals(3, $parsed['params'][7]);
        $this->assertEquals('50', $parsed['params'][8]);
        $this->assertEquals('100', $parsed['params'][9]);
    }

    public function testParseShorthandWithPlaceholder2()
    {
        $columns = [
            'username'     => 'admin',
            'email'        => 'test@test.com',
            'attempts>='   => '5',
            'role'         => null,
            'profile-'     => null,
            '%title%'      => 'CEO',
            '-%position%-' => 'Staff',
            'id'           => [1, 2, 3],
            'logins'       => '(50, 100)',
        ];

        $parsed = Expression::parseShorthand($columns, '$');

        $this->assertTrue(isset($parsed['expressions']));
        $this->assertTrue(isset($parsed['params']));
        $this->assertEquals(9, count($parsed['expressions']));
        $this->assertEquals("username = $1", $parsed['expressions'][0]);
        $this->assertEquals("email = $2", $parsed['expressions'][1]);
        $this->assertEquals("attempts >= $3", $parsed['expressions'][2]);
        $this->assertEquals("role IS NULL", $parsed['expressions'][3]);
        $this->assertEquals("profile IS NOT NULL", $parsed['expressions'][4]);
        $this->assertEquals("title LIKE $4", $parsed['expressions'][5]);
        $this->assertEquals("position NOT LIKE $5", $parsed['expressions'][6]);
        $this->assertEquals("id IN ($6, $7, $8)", $parsed['expressions'][7]);
        $this->assertEquals("logins BETWEEN $9 AND $10", $parsed['expressions'][8]);

        $this->assertEquals(10, count($parsed['params']));
        $this->assertEquals('admin', $parsed['params'][0]);
        $this->assertEquals('test@test.com', $parsed['params'][1]);
        $this->assertEquals('5', $parsed['params'][2]);
        $this->assertEquals('%CEO%', $parsed['params'][3]);
        $this->assertEquals('%Staff%', $parsed['params'][4]);
        $this->assertEquals(1, $parsed['params'][5]);
        $this->assertEquals(2, $parsed['params'][6]);
        $this->assertEquals(3, $parsed['params'][7]);
        $this->assertEquals('50', $parsed['params'][8]);
        $this->assertEquals('100', $parsed['params'][9]);
    }

    public function testParseShorthandWithPlaceholder3()
    {
        $columns = [
            'username'     => 'admin',
            'email'        => 'test@test.com',
            'attempts>='   => '5',
            'role'         => null,
            'profile-'     => null,
            '%title%'      => 'CEO',
            '-%position%-' => 'Staff',
            'id'           => [1, 2, 3],
            'logins'       => '(50, 100)',
        ];

        $parsed = Expression::parseShorthand($columns, ':');

        $this->assertTrue(isset($parsed['expressions']));
        $this->assertTrue(isset($parsed['params']));
        $this->assertEquals(9, count($parsed['expressions']));
        $this->assertEquals("username = :username", $parsed['expressions']['username']);
        $this->assertEquals("email = :email", $parsed['expressions']['email']);
        $this->assertEquals("attempts >= :attempts", $parsed['expressions']['attempts']);
        $this->assertEquals("role IS NULL", $parsed['expressions']['role']);
        $this->assertEquals("profile IS NOT NULL", $parsed['expressions']['profile']);
        $this->assertEquals("title LIKE :title", $parsed['expressions']['title']);
        $this->assertEquals("position NOT LIKE :position", $parsed['expressions']['position']);
        $this->assertEquals("id IN (:id1, :id2, :id3)", $parsed['expressions']['id']);
        $this->assertEquals("logins BETWEEN :logins1 AND :logins2", $parsed['expressions']['logins']);

        $this->assertEquals(10, count($parsed['params']));
        $this->assertEquals('admin', $parsed['params']['username']);
        $this->assertEquals('test@test.com', $parsed['params']['email']);
        $this->assertEquals('5', $parsed['params']['attempts']);
        $this->assertEquals('%CEO%', $parsed['params']['title']);
        $this->assertEquals('%Staff%', $parsed['params']['position']);
        $this->assertEquals(1, $parsed['params']['id1']);
        $this->assertEquals(2, $parsed['params']['id2']);
        $this->assertEquals(3, $parsed['params']['id3']);
        $this->assertEquals('50', $parsed['params']['logins1']);
        $this->assertEquals('100', $parsed['params']['logins2']);
    }

    public function testStripIdQuotes()
    {
        $this->assertEquals('username', Expression::stripIdQuotes('`username`'));
    }

}
