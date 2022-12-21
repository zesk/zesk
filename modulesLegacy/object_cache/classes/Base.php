<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\ObjectCache;

use zesk\ORM\ORMBase;

/**
 *
 * @author kent
 *
 */
abstract class Base {
	abstract public function load(ORMBase $object, $key);

	abstract public function save(ORMBase $object, $key, $data);

	abstract public function invalidate(ORMBase $object);
}
