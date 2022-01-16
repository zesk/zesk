<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * Override this in subclasses.
 *
 * @author kent
 * @see Class_Zesk_Role
 * @see Role
 */
class Class_Role extends Class_ORM {
	public string $id_column = "id";

	public $name_column = "name";

	public array $find_keys = [
		'code',
	];

	public array $column_types = [
		"id" => self::type_id,
		"code" => self::type_string,
		"name" => self::type_string,
		'is_root' => self::type_boolean,
		'is_default' => self::type_boolean,
	];

	public array $has_many = [
		'users' => [
			'class' => 'zesk\\User',
			'link_class' => 'zesk\\User_Role',
			'foreign_key' => 'role',
			'far_key' => 'user',
		],
	];

	public string $database_group = "zesk\\User";
}
