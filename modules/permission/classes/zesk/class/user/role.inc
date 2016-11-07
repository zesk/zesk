<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Class_User_Role extends Class_Object {
	/**
	 * 
	 * @var array
	 */
	public $primary_keys = array(
		"user",
		"role"
	);
	public $id_column = false;
	public $column_types = array(
		"user" => self::type_object,
		"role" => self::type_object,
		"created" => self::type_created,
		"creator" => self::type_object
	);
	public $has_one = array(
		"user" => 'User',
		"role" => 'Role',
		"creator" => 'User'
	);
	public $database_group = "User";
}

