<?php

namespace Pop\Db\Test;

use Pop\Db\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{

    public function testParserColumn()
    {
        $this->assertEquals('user_info', Parser\Table::parse('UserInfo'));
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
