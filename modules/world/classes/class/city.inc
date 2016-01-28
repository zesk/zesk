<?php
class Class_City extends Class_Object {
	public $id_column = "id";
	public $find_keys = array(
		"name"
	);
	public $has_one = array(
		'province' => 'Province',
		'county' => 'County'
	);
	public $column_types = array(
		"id" => self::type_id,
		"province" => self::type_object,
		"county" => self::type_object,
		'name' => self::type_string
	);
}
