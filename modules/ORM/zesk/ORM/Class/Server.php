<?php declare(strict_types=1);

/**
 * @copyright 2016 &copy; Market Acumen, Inc.
 * @author kent
 */
namespace zesk\ORM;

/**
 * @see Server
 * @author kent
 * Class definition for Server
 */
class Class_Server extends Class_Base {
	public const LOCALHOST_IP = '127.0.0.1';

	public string $id_column = 'id';

	public array $find_keys = [
		'name',
	];

	public array $duplicate_keys = [
		'ip4_internal',
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'name' => self::TYPE_STRING,
		'name_internal' => self::TYPE_STRING,
		'name_external' => self::TYPE_STRING,
		'ip4_internal' => self::TYPE_IP4,
		'ip4_external' => self::TYPE_IP4,
		'free_disk' => self::TYPE_INTEGER,
		'free_disk_units' => self::TYPE_STRING,
		'load' => self::TYPE_DOUBLE,
		'alive' => self::TYPE_MODIFIED,
	];

	public array $has_many = [
		'metas' => [
			'class' => ServerMeta::class,
			'foreign_key' => 'server',
		],
	];

	public array $column_defaults = [
		'ip4_internal' => self::LOCALHOST_IP,
		'ip4_external' => self::LOCALHOST_IP,
		'free_disk_units' => Server::DISK_UNITS_BYTES,
	];
}
