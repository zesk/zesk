<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * @see Lock
 * @author kent
 *
 */
class Class_Lock extends Class_ORM {
	public string $id_column = 'id';

	public array $has_one = [
		'server' => 'zesk\\Server',
	];

	public array $find_keys = [
		'code',
	];

	public array $column_types = [
		'id' => self::type_id,
		'code' => self::type_string,
		'pid' => self::type_integer,
		'server' => self::type_object,
		'locked' => self::type_timestamp,
		'used' => self::type_timestamp,
	];
}
