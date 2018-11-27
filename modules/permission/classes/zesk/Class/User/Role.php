<?php
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
	public $primary_keys = array(
		"user",
		"role",
	);

	public $id_column = false;

	public $column_types = array(
		"user" => self::type_object,
		"role" => self::type_object,
		"created" => self::type_created,
		"creator" => self::type_object,
	);

	public $has_one = array(
		"user" => 'zesk\User',
		"role" => 'zesk\Role',
		"creator" => 'zesk\User',
	);

	public $database_group = "zesk\User";
}
