<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Class_Lock extends Class_Object {
	public $id_column = "id";
	public $has_one = array(
		'server' => "zesk\\Server"
	);
	public $find_keys = array(
		'code'
	);
	public $column_types = array(
		'id' => self::type_id,
		'code' => self::type_string,
		'pid' => self::type_integer,
		'server' => self::type_object,
		'locked' => self::type_timestamp,
		'used' => self::type_timestamp
	);
}
