<?php declare(strict_types=1);

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
	public const LOCALHOST_IP = '127.0.0.1';

	public string $id_column = "id";

	public array $find_keys = [
		"name",
	];

	public array $column_types = [
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
	];

	public array $has_many = [
		'data' => [
			'class' => 'zesk\\Server_Data',
			'foreign_key' => 'server',
		],
	];

	public array $column_defaults = [
		'ip4_internal' => self::LOCALHOST_IP,
		'ip4_external' => self::LOCALHOST_IP,
		'free_disk_units' => Server::DISK_UNITS_BYTES,
	];
}
