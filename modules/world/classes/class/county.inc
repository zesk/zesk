<?php
class Class_County extends Class_Object {
	public $id_column = "id";
	public $name_column = "name";
	public $has_one = array(
		'province' => "Province"
	);
	public $column_types = array(
		'id' => self::type_id,
		'province' => self::type_object,
		'name' => self::type_string
	);
}