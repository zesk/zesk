<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 * @see User_Role
 */
class Class_User_Role extends Class_Base {
	/**
	 *
	 * @var array
	 */
	public array $primary_keys = [
		'user',
		'role',
	];

	public array $column_types = [
		'user' => self::TYPE_OBJECT,
		'role' => self::TYPE_OBJECT,
		'created' => self::TYPE_CREATED,
		'creator' => self::TYPE_OBJECT,
	];

	public array $has_one = [
		'user' => 'zesk\User',
		'role' => 'zesk\Role',
		'creator' => 'zesk\User',
	];

	public string $database_group = "zesk\User";
}
