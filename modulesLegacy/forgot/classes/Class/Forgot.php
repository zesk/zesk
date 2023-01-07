<?php declare(strict_types=1);
namespace zesk;

use zesk\Session\Session\classes\SessionORM;

/**
 * @see Forgot
 */
class Class_Forgot extends Class_Base {
	/**
	 *
	 * @var string
	 */
	public string $id_column = 'id';

	/**
	 *
	 * @var array
	 */
	public array $find_keys = [
		'code',
	];

	/**
	 *
	 * @var array
	 */
	public array $column_types = [
		'id' => self::TYPE_ID,
		'login' => self::TYPE_STRING,
		'user' => self::TYPE_OBJECT,
		'session' => self::TYPE_OBJECT,
		'code' => self::TYPE_HEX,
		'created' => self::TYPE_CREATED,
		'updated' => self::TYPE_TIMESTAMP,
	];

	/**
	 *
	 * @var array
	 */
	public array $has_one = [
		'user' => User::class,
		'session' => SessionORM::class,
	];

	/**
	 *
	 * @var string
	 */
	public string $database_group = User::class;
}
