<?php

class Class_Filter_State extends zesk\Class_Object {
	public $id_column = "id";

	public $column_types = array(
		'id' => self::type_id,
		'scope' => self::type_string,
		'user' => self::type_object,
		'name' => self::type_string,
		'filters' => self::type_string
	);
}
