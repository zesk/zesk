<?php
namespace zesk;

class Class_Filter_State extends Class_ORM {
	public $id_column = "id";
	public $has_one = array(
		"user" => "zesk\\User"
	);
	public $column_types = array(
		'id' => self::type_id,
		'scope' => self::type_string,
		'user' => self::type_object,
		'name' => self::type_string,
		'filters' => self::type_string
	);
}
