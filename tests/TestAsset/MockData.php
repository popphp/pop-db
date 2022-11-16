<?php

namespace Pop\Db\Test\TestAsset;

use ReturnTypeWillChange;

class MockData implements \ArrayAccess
{

    protected $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function toArray()
    {
        return $this->data;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->data[$name];
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        if (isset($this->data[$name])) {
            unset($this->data[$name]);
        }
    }

    public function offsetExists($offset): bool
    {
        return $this->__isset($offset);
    }

    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    #[ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

}
