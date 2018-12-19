<?php
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
abstract class Object_Cache {
	abstract public function load(ORM $object, $key);

	abstract public function save(ORM $object, $key, $data);

	abstract public function invalidate(ORM $object);
}
