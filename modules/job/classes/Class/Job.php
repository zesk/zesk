<?php

namespace zesk;

/**
 * @see Job
 * @author kent
 *
 */
class Class_Job extends Class_Object {
	public $id_column = "id";
	public $find_keys = array(
		"code"
	);
	public $has_one = array(
		"user" => "zesk\\User",
		"server" => "zesk\\Server"
	);
	public $column_types = array(
		'id' => self::type_id,
		'user' => self::type_object,
		'name' => self::type_string,
		'code' => self::type_string,
		'created' => self::type_created,
		'start' => self::type_timestamp,
		'server' => self::type_object,
		'priority' => self::type_integer,
		'pid' => self::type_integer,
		'completed' => self::type_datetime,
		'updated' => self::type_datetime,
		'duration' => self::type_integer,
		'died' => self::type_integer,
		'last_exit' => self::type_boolean,
		'progress' => self::type_double,
		'hook' => self::type_string,
		'hook_args' => self::type_serialize,
		'data' => self::type_serialize,
		'status' => self::type_string
	);
	public $column_defaults = array(
		"duration" => 0,
		"died" => 0,
		"status" => ""
	);
	public function initialize() {
		$foo = 1;
	}
}
