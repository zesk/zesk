<?php
namespace zesk;

abstract class Stream {
    abstract public function read($length);

    abstract public function write($data, $length = null);

    abstract public function offset($set = null);
}
