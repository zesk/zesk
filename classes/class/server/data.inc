<?php

/**
 * @see Server_Data
 * @author kent
 *
 */
class Class_Server_Data extends Class_Object {
	public $id_column = "id";
	public $primary_keys = array(
		"id"
	);
	public $find_keys = array(
		"server",
		"name"
	);
	public $column_types = array(
		'id' => self::type_id,
		'server' => self::type_object,
		'name' => self::type_string,
		'value' => self::type_serialize
	);
	public $has_one = array(
		'server' => 'Server'
	);
	public $database_group = "Server";
}