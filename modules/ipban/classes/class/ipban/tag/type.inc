<?php

class Class_IPBan_Tag_Type extends zesk\Class_Object {

	public $find_keys = array(
		'name'
	);

	public $columns = array(
		"id",
		"name"
	);

	public $column_types = array(
		"id" => self::type_id,
		"name" => self::type_string
	);

	public $id_column = "id";

	public $auto_column = "id";
}
