<?php
class Class_Country extends zesk\Class_Object {
	public $id_column = "id";
	public $find_keys = array(
		"code"
	);
	public $column_types = array(
		'id' => self::type_id,
		'code' => self::type_string,
		'name' => self::type_string
	);
}

