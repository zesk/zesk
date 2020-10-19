<?php

/**
 * @copyright 2016 &copy; Market Acumen, Inc.
 * @author kent
 */
namespace zesk;

/**
 * @see Server
 * @author kent
 * Class definition for Server
 */
class Class_Server extends Class_ORM {
	const LOCALHOST = 2130706433;       // IPv4::to_integer('127.0.0.1')

	public $id_column = "id";

	public $find_keys = array(
		"name",
	);

	public $column_types = array(
		'id' => self::type_id,
		'name' => self::type_string,
		'name_internal' => self::type_string,
		'name_external' => self::type_string,
		'ip4_internal' => self::type_ip4,
		'ip4_external' => self::type_ip4,
		'free_disk' => self::type_integer,
		'free_disk_units' => self::type_string,
		'load' => self::type_double,
		'alive' => self::type_modified,
	);

	public $has_many = array(
		'data' => array(
			'class' => 'zesk\\Server_Data',
			'foreign_key' => 'server',
		),
	);

	public $column_defaults = array(
		'ip4_internal' => self::LOCALHOST,
		'ip4_external' => self::LOCALHOST,
		'free_disk_units' => Server::DISK_UNITS_BYTES,
	);
}
