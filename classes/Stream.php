<?php
namespace zesk;

abstract class Stream {
	abstract function read($length);
	abstract function write($data, $length = null);
	abstract function offset($set = null);
}
