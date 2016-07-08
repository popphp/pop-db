<?php

namespace Pop\Db\Test;

use Pop\Db\Db;
use Pop\Db\Record\Result;

class ResultTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructorSetDb()
    {
        $db     = Db::connect('sqlite', ['database' => __DIR__  . '/tmp/db.sqlite']);
        $result = new Result($db, 'users', 'id');
        $this->assertInstanceOf('Pop\Db\Record\Result', $result);
    }

}