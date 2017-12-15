<?php
namespace zesk;

class Class_TestORM extends Class_ORM {
	public $id_column = "ID";
	public $column_types = array(
		"ID" => self::type_id,
		'Name' => self::type_string,
		'Price' => self::type_double,
		'Foo' => self::type_integer
	);
}
class TestORM extends ORM {
	function schema() {
		$table = $this->table;
		return array(
			"CREATE TABLE $table ( ID integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY, Name varchar(32) NOT NULL, Price decimal(12,2), Foo integer NULL )"
		);
	}
	function specification() {
		return array(
			"table" => get_class($this),
			"fields" => "Foo",
			"find_keys" => array(
				"Foo"
			)
		);
	}
}

