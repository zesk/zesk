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
class Class_User_Role extends Class_ORM {
	/**
	 *
	 * @var array
	 */
	public array $primary_keys = [
		"user",
		"role",
	];

	public array $column_types = [
		"user" => self::type_object,
		"role" => self::type_object,
		"created" => self::type_created,
		"creator" => self::type_object,
	];

	public array $has_one = [
		"user" => 'zesk\User',
		"role" => 'zesk\Role',
		"creator" => 'zesk\User',
	];

	public string $database_group = "zesk\User";
}
