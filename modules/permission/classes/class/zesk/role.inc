<?php

class Class_Zesk_Role extends Class_Object {
	public $id_column = "id";

	public $name_column = "name";

	public $find_keys = array(
		'code'
	);
	public $column_types = array(
		"id" => self::type_id,
		"code" => self::type_string,
		"name" => self::type_string,
		'is_root' => 'boolean',
		'is_default' => 'boolean'
	);
	public $has_many = array(
		'users' => array(
			'class' => 'User',
			'table' => 'User_Role',
			'foreign_key' => 'role',
			'far_key' => 'user'
		)
	);

	public $database_group = "User";
}