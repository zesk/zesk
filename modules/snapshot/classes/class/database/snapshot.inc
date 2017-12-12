<?php
namespace zesk;

class Class_Database_Snapshot extends Class_ORM {
	public $id_column = "id";
	public $column_types = array(
		'id' => self::type_id,
		'name' => self::type_string,
		'dbname' => self::type_string,
		'elapsed' => self::type_double,
		'created' => self::type_string,
		'data' => self::type_string
	);
}
