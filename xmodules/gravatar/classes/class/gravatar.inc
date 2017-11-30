<?php
class Class_Gravatar extends zesk\Class_Object {
	public $id_column = 'id';
	public $find_columns = array(
		'email'
	);
	public $column_types = array(
		'id' => self::type_id,
		'email' => self::type_string,
		'status' => self::type_boolean,
		'profile_url' => self::type_string,
		'username' => self::type_string,
		'thumbnail_url' => self::type_string,
		'display_name' => self::type_string,
		'last_result' => self::type_serialize,
		'created' => self::type_created,
		'updated' => self::type_timestamp
	);
	public $column_defaults = array(
		'status' => 0
	);
}
