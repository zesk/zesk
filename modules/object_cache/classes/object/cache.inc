<?php
/**
 * 
 */
use zesk\Object;

/**
 * 
 * @author kent
 *
 */
abstract class Object_Cache {

	abstract public function load(Object $object, $key);

	abstract public function save(Object $object, $key, $data);

	abstract public function invalidate(Object $object);
}
