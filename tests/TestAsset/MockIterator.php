<?php

namespace Pop\Db\Test\TestAsset;

class MockIterator implements \IteratorAggregate
{

    protected $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

}
