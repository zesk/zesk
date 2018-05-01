<?php
namespace zesk;

/**
 * @see Forgot
 */
class Class_Forgot extends Class_ORM {
	public $id_column = "id";
	public $find_keys = array(
		'code'
	);
	public $column_types = array(
		"id" => self::type_id,
		"login" => self::type_string,
		"user" => self::type_object,
		"session" => self::type_object,
		"code" => self::type_hex,
		"created" => self::type_created,
		'updated' => self::type_timestamp
	);
	public $has_one = array(
		"user" => "zesk\\User",
		"session" => "zesk\\Session_ORM"
	);
	public $database_group = "zesk\\User";
}
