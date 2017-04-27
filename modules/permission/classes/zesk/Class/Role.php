<?php
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
class Class_Role extends Class_Object {
	public $id_column = "id";
	public $name_column = "name";
	public $find_keys = array(
		'code'
	);
	public $column_types = array(
		"id" => self::type_id,
		"code" => self::type_string,
		"name" => self::type_string,
		'is_root' => self::type_boolean,
		'is_default' => self::type_boolean
	);
	public $has_many = array(
		'users' => array(
			'class' => 'zesk\\User',
			'link_class' => 'zesk\\User_Role',
			'foreign_key' => 'role',
			'far_key' => 'user'
		)
	);
	public $database_group = "zesk\\User";
}
