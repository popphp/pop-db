<?php

namespace Pop\Db\Test;

use Pop\Db\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{

    public function testParserColumn1()
    {
        $results = Parser\Column::parse([
            '%username1%'   => 'test',
            'username2%'    => 'test',
            '%username3'    => 'test',
            '-%username4'   => 'test',
            'username5%-'   => 'test',
            '-%username6%-' => 'test',
            'username7'     => null,
            'username-'     => null,
            'id' => [2, 3],
            'id-' => [2, 3],
            'id1' => '(1, 5)',
            'id1-' => '(1, 5)'
        ], '?');

        $this->assertContains('username1 LIKE ?', $results['where']);
        $this->assertContains('username2 LIKE ?', $results['where']);
        $this->assertContains('username3 LIKE ?', $results['where']);
        $this->assertContains('username4 NOT LIKE ?', $results['where']);
        $this->assertContains('username5 NOT LIKE ?', $results['where']);
        $this->assertContains('username6 NOT LIKE ?', $results['where']);
        $this->assertContains('username7 IS NULL', $results['where']);
        $this->assertContains('username IS NOT NULL', $results['where']);
        $this->assertContains('id IN (2, 3)', $results['where']);
        $this->assertContains('id NOT IN (2, 3)', $results['where']);
        $this->assertContains('id1 BETWEEN (1, 5)', $results['where']);
        $this->assertContains('id1 NOT BETWEEN (1, 5)', $results['where']);
        $this->assertEquals('%test%', $results['params']['username1']);
        $this->assertEquals('test%', $results['params']['username2']);
        $this->assertEquals('%test', $results['params']['username3']);
        $this->assertEquals('%test', $results['params']['username4']);
        $this->assertEquals('test%', $results['params']['username5']);
        $this->assertEquals('%test%', $results['params']['username6']);
    }

    public function testParserColumn2()
    {
        $results = Parser\Column::parse([
            '%username1%'   => 'test',
            'username2%'    => 'test',
            '%username3'    => 'test',
            '-%username4'   => 'test',
            'username5%-'   => 'test',
            '-%username6%-' => 'test'
        ], ':');

        $this->assertContains('username1 LIKE :username1', $results['where']);
        $this->assertContains('username2 LIKE :username2', $results['where']);
        $this->assertContains('username3 LIKE :username3', $results['where']);
        $this->assertContains('username4 NOT LIKE :username4', $results['where']);
        $this->assertContains('username5 NOT LIKE :username5', $results['where']);
        $this->assertContains('username6 NOT LIKE :username6', $results['where']);
        $this->assertEquals('%test%', $results['params']['username1']);
        $this->assertEquals('test%', $results['params']['username2']);
        $this->assertEquals('%test', $results['params']['username3']);
        $this->assertEquals('%test', $results['params']['username4']);
        $this->assertEquals('test%', $results['params']['username5']);
        $this->assertEquals('%test%', $results['params']['username6']);
    }

    public function testParserColumnOr()
    {
        $results = Parser\Column::parse([
            '%username% OR' => 'test'
        ], ':');

        $this->assertContains('username LIKE :username OR', $results['where']);
    }

    public function testParserColumn3()
    {
        $results = Parser\Column::parse([
            'username' => 'test'
        ], '$');

        $this->assertContains('username = $1', $results['where']);
    }

    public function testParserOperator()
    {
        $this->assertEquals(['column' => 'username', 'op' => '>='], Parser\Operator::parse('username>='));
        $this->assertEquals(['column' => 'username', 'op' => '<='], Parser\Operator::parse('username<='));
        $this->assertEquals(['column' => 'username', 'op' => '!='], Parser\Operator::parse('username!='));
        $this->assertEquals(['column' => 'username', 'op' => '>'], Parser\Operator::parse('username>'));
        $this->assertEquals(['column' => 'username', 'op' => '<'], Parser\Operator::parse('username<'));
        $this->assertEquals(['column' => 'username', 'op' => '='], Parser\Operator::parse('username'));
    }

    public function testParserTable()
    {
        $this->assertEquals('user_info', Parser\Table::parse('UserInfo'));
    }

}
