<?php

class Class_Browser extends zesk\Class_Object {
	public $id_column = "id";

	public $find_keys = array(
		'md5'
	);
	public $column_types = array(
		'id' => self::type_id,
		'md5' => self::type_hex32,
		'is_phone' => self::type_boolean,
		'is_tablet' => self::type_boolean,
		'is_desktop' => self::type_boolean,
		'tags' => self::type_serialize,
		'name' => self::type_string,
		'created' => self::type_created,
		'parsed' => self::type_timestamp,
	);
}
