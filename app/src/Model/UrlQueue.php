<?php

namespace PopSpider\Model;

class UrlQueue implements \Iterator, \ArrayAccess
{

    protected $position = 0;
    protected $array    = [];

    public function __construct() {
        $this->position = 0;
    }

    function rewind() {
        $this->position = 0;
    }

    function current() {
        return $this->array[$this->position];
    }

    function key() {
        return $this->position;
    }

    function prev() {
        --$this->position;
        return $this->current();
    }

    function next() {
        ++$this->position;
        return $this->current();
    }

    function valid() {
        return isset($this->array[$this->position]);
    }

    public function offsetSet($offset, $value) {
        if (null === $offset) {
            $this->array[] = $value;
        } else {
            $this->array[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->array[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->array[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->array[$offset]) ? $this->array[$offset] : null;
    }

    public function __set($name, $value) {
        $this->offsetSet($name, $value);
    }

    public function __get($name) {
        return $this->offsetGet($name);
    }

    public function __unset($name) {
        $this->offsetUnset($name);
    }

    public function __isset($name) {
        return $this->offsetExists($name);
    }

}