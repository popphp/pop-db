<?php

namespace Pop\Db\Test\Sql\Parser;

use Pop\Db\Sql\Parser\Keyword;
use PHPUnit\Framework\TestCase;

class KeywordTest extends TestCase
{

    public function testIndexOfFindsMatchOutsideQuotes()
    {
        $this->assertEquals(17, Keyword::indexOf("status = 'active' AND role = 'admin'", ' AND ', 0, false));
    }

    public function testIndexOfIgnoresMatchInsideSingleQuotes()
    {
        $this->assertFalse(Keyword::indexOf("status = 'PENDING BETWEEN REVIEW'", ' BETWEEN '));
    }

    public function testIndexOfIgnoresMatchInsideDoubleQuotes()
    {
        $this->assertFalse(Keyword::indexOf('status = "PENDING BETWEEN REVIEW"', ' BETWEEN '));
    }

    public function testIndexOfIgnoresMatchInsideBacktickQuotedIdentifier()
    {
        $this->assertFalse(Keyword::indexOf('`PENDING BETWEEN REVIEW` = 1', ' BETWEEN '));
    }

    public function testIndexOfIsCaseInsensitiveByDefault()
    {
        $this->assertEquals(7, Keyword::indexOf('status between 1 and 2', 'BETWEEN'));
    }

    public function testIndexOfCaseSensitiveModeDoesNotMatchLowercase()
    {
        $this->assertFalse(Keyword::indexOf('status between 1 and 2', 'BETWEEN', 0, false));
    }

    public function testSplitOnAndOr()
    {
        $this->assertEquals(
            ['id = ?', 'AND', 'email = ?'],
            Keyword::split('id = ? AND email = ?')
        );
    }

    public function testSplitIgnoresAndOrInsideQuotedValue()
    {
        $this->assertEquals(
            ["name = 'JOHNSON AND JOHNSON'"],
            Keyword::split("name = 'JOHNSON AND JOHNSON'")
        );
    }

    public function testSplitIgnoresAndInsideBacktickQuotedIdentifier()
    {
        $this->assertEquals(
            ['`AND` = 1'],
            Keyword::split('`AND` = 1')
        );
    }

    public function testSplitRequiresWordBoundary()
    {
        $this->assertEquals(
            ["brand = 'Nike'"],
            Keyword::split("brand = 'Nike'")
        );
    }

    public function testSplitHandlesMultipleConjunctions()
    {
        $this->assertEquals(
            ['a = 1', 'AND', 'b = 2', 'OR', 'c = 3'],
            Keyword::split('a = 1 AND b = 2 OR c = 3')
        );
    }

}
