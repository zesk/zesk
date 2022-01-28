<?php declare(strict_types=1);
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Class_DBQueryObjectTest extends Class_ORM {
	public string $id_column = "id";

	public array $column_types = [
		"id" => self::type_id,
		"foo" => self::type_string,
	];

	public function schema(ORM $object): string|array|ORM_Schema {
		return "CREATE TABLE {table} ( id integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT, foo varchar(128) )";
	}
}

/**
 *
 * @author kent
 *
 */
class DBQueryObjectTest extends ORM {
	public function validate(): void {
		$test = Database_Test::$test;
		$test->assert(!$this->member_is_empty("id"));
		$test->assert(!$this->member_is_empty("foo"));
	}
}
