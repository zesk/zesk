<?php
class Class_ObjectX_Field extends zesk\Class_Object {
	public $id_column = "id";
	public $find_keys = array(
		'class',
		'column'
	);
	public $column_types = array(
		"id" => self::type_id,
		"class" => self::type_string,
		"active" => self::type_string,
		"column" => self::type_string,
		"name" => self::type_string,
		"help" => self::type_string,
		"type" => self::type_string,
		"order_index" => self::type_integer,
		"data" => self::type_serialize
	);
}
