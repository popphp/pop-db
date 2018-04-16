<?php

namespace Pop\Db\Test;

use Pop\Db\Record;

class CollectionTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $collection = new Record\Collection([
            [
                'id'   => 1,
                'name' => 'John'
            ],
            [
                'id'   => 2,
                'name' => 'Jane'
            ]
        ]);
        $this->assertInstanceOf('Pop\Db\Record\Collection', $collection);
    }

    public function testFirstAndLast()
    {
        $collection = new Record\Collection([
            [
                'id'   => 1,
                'name' => 'John'
            ],
            [
                'id'   => 2,
                'name' => 'Jane'
            ]
        ]);

        $first = $collection->first();
        $last  = $collection->last();
        $this->assertEquals(1, $first['id']);
        $this->assertEquals(2, $last['id']);
    }

    public function testNextAndCurrent()
    {
        $collection = new Record\Collection([
            [
                'id'   => 1,
                'name' => 'John'
            ],
            [
                'id'   => 2,
                'name' => 'Jane'
            ]
        ]);

        $next    = $collection->next();
        $current = $collection->current();
        $this->assertEquals(2, $next['id']);
        $this->assertEquals(2, $current['id']);
        $this->assertEquals(1, $collection->key());
    }

    public function testContains()
    {
        $collection = new Record\Collection([
            [
                'id'   => 1,
                'name' => 'John'
            ],
            [
                'id'   => 2,
                'name' => 'Jane'
            ]
        ]);

        $this->assertTrue($collection->contains([
            'id'   => 1,
            'name' => 'John'
        ]));
    }

    public function testHas()
    {
        $collection = new Record\Collection([
            [
                'id'   => 1,
                'name' => 'John'
            ],
            [
                'id'   => 2,
                'name' => 'Jane'
            ]
        ]);

        $this->assertTrue($collection->has(1));
    }

    public function testIsEmpty()
    {
        $collection = new Record\Collection([
            [
                'id'   => 1,
                'name' => 'John'
            ],
            [
                'id'   => 2,
                'name' => 'Jane'
            ]
        ]);

        $this->assertFalse($collection->isEmpty());
    }

    public function testKeysAndValues()
    {
        $collection = new Record\Collection([
            [
                'id'   => 1,
                'name' => 'John'
            ],
            [
                'id'   => 2,
                'name' => 'Jane'
            ]
        ]);

        $this->assertEquals(2, count($collection->keys()));
        $this->assertEquals(2, count($collection->values()));
    }

}