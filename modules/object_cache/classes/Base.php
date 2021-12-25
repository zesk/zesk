<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\ObjectCache;

use zesk\ORM;

/**
 *
 * @author kent
 *
 */
abstract class Base {
	abstract public function load(ORM $object, $key);

	abstract public function save(ORM $object, $key, $data);

	abstract public function invalidate(ORM $object);
}
