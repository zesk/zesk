<?php
namespace zesk;

class Class_Mail_Header_Type extends Class_Object {
	public $id_column = "id";
	public $find_keys = array(
		"code"
	);
	public $column_types = array(
		'id' => self::type_id,
		'code' => self::type_string,
		'name' => self::type_string,
		'ignore' => self::type_boolean,
		'created' => self::type_created,
		'modified' => self::type_modified
	);
}
