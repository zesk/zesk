<?php
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Class_DBQueryObjectTest extends Class_ORM {
	public $id_column = "id";
	public $column_types = array(
		"id" => self::type_id,
		"foo" => self::type_string
	);
}

/**
 *
 * @author kent
 *
 */
class DBQueryObjectTest extends ORM {
	function validate() {
		$test = Database_Test::$test;
		$test->assert(!$this->member_is_empty("id"));
		$test->assert(!$this->member_is_empty("foo"));
	}
}
