<?php

namespace Pop\Db\Test\Sql\Parser;

use Pop\Db\Sql\Parser\Table;
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{

    public function testParseAsc()
    {
        $this->assertEquals('user_profiles', Table::parse('userProfiles'));
    }

}