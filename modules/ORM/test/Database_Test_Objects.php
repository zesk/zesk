<?php declare(strict_types=1);
namespace zesk\ORM;

use zesk\UnitTest;

/**
 *
 * @author kent
 *
 */
class Class_DBQueryObjectTest extends Class_Base {
	public string $id_column = 'id';

	public array $column_types = [
		'id' => self::TYPE_ID,
		'foo' => self::TYPE_STRING,
	];

	public function schema(ORMBase $object): string|array|Schema {
		return 'CREATE TABLE {table} ( id integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT, foo varchar(128) )';
	}
}

/**
 *
 * @author kent
 *
 */
class DBQueryObjectTest extends ORMBase {
	public function validate(UnitTest $test): void {
		$test->assertTrue(!$this->memberIsEmpty('id'));
		$test->assertTrue(!$this->memberIsEmpty('foo'));
	}
}
