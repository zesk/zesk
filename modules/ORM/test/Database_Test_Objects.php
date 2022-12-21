<?php declare(strict_types=1);
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Class_DBQueryObjectTest extends Class_Base {
	public string $id_column = 'id';

	public array $column_types = [
		'id' => self::type_id,
		'foo' => self::type_string,
	];

	public function schema(ORMBase $object): string|array|ORM_Schema {
		return 'CREATE TABLE {table} ( id integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT, foo varchar(128) )';
	}
}

/**
 *
 * @author kent
 *
 */
class DBQueryObjectTest extends ORMBase {
	public function validate(): void {
		$test = Database_Test::$test;
		$test->assert(!$this->memberIsEmpty('id'));
		$test->assert(!$this->memberIsEmpty('foo'));
	}
}
