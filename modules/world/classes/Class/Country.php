<?php
namespace zesk;

class Class_Country extends Class_ORM {
	public $id_column = "id";

	public $find_keys = array(
		"code",
	);

	public $column_types = array(
		'id' => self::type_id,
		'code' => self::type_string,
		'name' => self::type_string,
	);
}
