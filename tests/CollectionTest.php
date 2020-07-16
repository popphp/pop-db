<?php

namespace Pop\Db\Test;

use Pop\Db\Record;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
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
        $this->assertEquals(2, count($collection->getItems()));
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

    public function testEach()
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

        ob_start();
        $collection->each(function($item, $key) {
            echo $item['name'];
        });
        $contents = ob_get_clean();

        $this->assertEquals('JohnJane', $contents);
    }

    public function testEvery()
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

        $newCollection = $collection->every(2);

        $this->assertEquals(1, $newCollection->count());
    }

    public function testFilter()
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

        $newCollection = $collection->filter(function($item) {
            if ($item['id'] == 1) {
                return $item;
            }
        });

        $this->assertEquals(1, $newCollection->count());
    }

    public function testFlip()
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

        $newCollection = $collection->flip();

        $this->assertEquals('name', $newCollection[0]['John']);
    }

    public function testMerge()
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

        $newCollection = $collection->merge([
            [
                'id'   => 3,
                'name' => 'Bob'
            ],
            [
                'id'   => 4,
                'name' => 'Billy'
            ]
        ]);

        $this->assertEquals(4, $newCollection->count());
        $this->assertEquals(2, $newCollection->forPage(2, 2)->count());
    }

    public function testPop()
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

        $item = $collection->pop();

        $this->assertEquals(2, count($item));
        $collection->push(            [
            'id'   => 3,
            'name' => 'Bob'
        ]);

        $this->assertEquals(2, $collection->count());
    }

    public function testShift()
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

        $item = $collection->shift();

        $this->assertEquals(2, count($item));
        $this->assertEquals(1, $collection->count());
    }

    public function testSplice()
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

        $newCollection = $collection->splice(0, 1);

        $this->assertEquals(1, $newCollection->count());
    }

    public function testSort()
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

        $newCollection1 = $collection->sortByAsc();
        $newCollection2 = $collection->sortByDesc();
        $newCollection3 = $collection->sort();

        $this->assertEquals(2, $newCollection1->count());
        $this->assertEquals(2, $newCollection2->count());
        $this->assertEquals(2, $newCollection3->count());
    }

    public function testToArray()
    {
        $collection = new Record\Collection([
            [
                'id'        => 1,
                'firstName' => 'John',
                'lastName'  => 'Smith',
            ],
            [
                'id'        => 2,
                'firstName' => 'Jane',
                'lastName'  => 'Smith',
            ],
            [
                'id'        => 3,
                'firstName' => 'Tom',
                'lastName'  => 'Washington',
            ],
        ]);

        $array1 = $collection->toArray();
        $array2 = $collection->toArray(['column' => 'id']);
        $array3 = $collection->toArray(['key' => 'id']);
        $array4 = $collection->toArray(['key' => 'lastName', 'isUnique' => false]);

        $expected1 = [
            [
                'id'        => 1,
                'firstName' => 'John',
                'lastName'  => 'Smith',
            ],
            [
                'id'        => 2,
                'firstName' => 'Jane',
                'lastName'  => 'Smith',
            ],
            [
                'id'        => 3,
                'firstName' => 'Tom',
                'lastName'  => 'Washington',
            ],
        ];
        $expected2 = [1, 2, 3];
        $expected3 = [
            1 => [
                'id'        => 1,
                'firstName' => 'John',
                'lastName'  => 'Smith',
            ],
            2 => [
                'id'        => 2,
                'firstName' => 'Jane',
                'lastName'  => 'Smith',
            ],
            3 => [
                'id'        => 3,
                'firstName' => 'Tom',
                'lastName'  => 'Washington',
            ],
        ];
        $expected4 = [
            'Smith' => [
                [
                    'id'        => 1,
                    'firstName' => 'John',
                    'lastName'  => 'Smith',
                ],
                [
                    'id'        => 2,
                    'firstName' => 'Jane',
                    'lastName'  => 'Smith',
                ],
            ],
            [
                'Washington' =>
                [
                    'id'        => 3,
                    'firstName' => 'Tom',
                    'lastName'  => 'Washington',
                ],
            ]
        ];

        $this->assertEquals(3, count($array1));
        $this->assertTrue($array1 === $expected1);
        $this->assertTrue($array2 === $expected2);
        $this->assertTrue($array3 === $expected3);
        $this->assertTrue($array4 === $expected4);
    }

    public function testUnset()
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

        unset($collection[0]);
        $this->assertEquals(1, $collection->count());
    }

}