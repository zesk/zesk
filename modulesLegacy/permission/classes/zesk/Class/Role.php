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
class Class_Role extends Class_Base {
	public string $id_column = 'id';

	public string $name_column = 'name';

	public array $find_keys = [
		'code',
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'code' => self::TYPE_STRING,
		'name' => self::TYPE_STRING,
		'is_root' => self::TYPE_BOOL,
		'is_default' => self::TYPE_BOOL,
	];

	public array $has_many = [
		'users' => [
			'class' => 'zesk\\User',
			'link_class' => 'zesk\\User_Role',
			'foreign_key' => 'role',
			'far_key' => 'user',
		],
	];

	public string $database_group = 'zesk\\User';
}
