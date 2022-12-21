<?php declare(strict_types=1);
namespace zesk;

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
		'id' => self::type_id,
		'login' => self::type_string,
		'user' => self::type_object,
		'session' => self::type_object,
		'code' => self::type_hex,
		'created' => self::type_created,
		'updated' => self::type_timestamp,
	];

	/**
	 *
	 * @var array
	 */
	public array $has_one = [
		'user' => User::class,
		'session' => Session_ORM::class,
	];

	/**
	 *
	 * @var string
	 */
	public string $database_group = User::class;
}
