<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\ORM;

/**
 * @see Lock
 * @author kent
 *
 */
class Class_Lock extends Class_Base {
	public string $id_column = 'id';

	public array $has_one = [
		'server' => Server::class,
	];

	public array $find_keys = [
		'code',
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'code' => self::TYPE_STRING,
		'pid' => self::TYPE_INTEGER,
		'server' => self::TYPE_OBJECT,
		'locked' => self::TYPE_TIMESTAMP,
		'used' => self::TYPE_TIMESTAMP,
	];
}
