<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Adapter\Sqlsrv;
use PHPUnit\Framework\TestCase;

class SqlsrvTest extends TestCase
{

    /**
     * escape() needs no live connection, so the adapter is built without its constructor
     * (which would try to connect to a SQL Server that isn't available to the test suite)
     */
    protected function createUnconnectedAdapter(): Sqlsrv
    {
        return new \ReflectionClass(Sqlsrv::class)->newInstanceWithoutConstructor();
    }

    public function testEscapeDoublesSingleQuotes()
    {
        $db = $this->createUnconnectedAdapter();
        $this->assertSame("O''Brien", $db->escape("O'Brien"));
        $this->assertSame("''''", $db->escape("''"));
    }

    public function testEscapeLeavesOtherCharactersAlone()
    {
        $db = $this->createUnconnectedAdapter();
        // SQL Server string literals have no backslash escapes
        $this->assertSame('back\\slash', $db->escape('back\\slash'));
        $this->assertSame("line1\nline2", $db->escape("line1\nline2"));
        $this->assertSame('say "hi"', $db->escape('say "hi"'));
    }

    public function testEscapeEmptyValues()
    {
        $db = $this->createUnconnectedAdapter();
        $this->assertSame('0', $db->escape('0'));
        $this->assertSame('', $db->escape(''));
        $this->assertSame('', $db->escape(null));
    }

}
