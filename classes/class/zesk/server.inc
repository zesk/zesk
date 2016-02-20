<?php
/**
 * @copyright 2013 &copy; Market Acumen, Inc.
 * @author kent
 */

/**
 * @see Zesk_Server
 * @author kent
 * Class definition for Server
 */
class Class_Zesk_Server extends Class_Object {
	public $id_column = "id";
	public $find_keys = array(
		"name"
	);
	public $column_types = array(
		'id' => self::type_id,
		'name' => self::type_string,
		'name_internal' => self::type_string,
		'name_external' => self::type_string,
		'ip4_internal' => self::type_ip4,
		'ip4_external' => self::type_ip4,
		'free_disk' => self::type_integer,
		'load' => self::type_double,
		'alive' => self::type_modified
	);
	public $has_many = array(
		'data' => array(
			'class' => 'Server_Data',
			'foreign_key' => 'server'
		)
	);
}