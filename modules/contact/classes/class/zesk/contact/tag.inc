<?php

/**
 * Class_Zesk_Contact_Tag
 *
 * @author kent
 */
class Class_Zesk_Contact_Tag extends Class_Object {

	public $has_one = array(
		'user' => 'User'
	);

	public $has_many = array(
		"contact" => array(
			'class' => 'Contact',
			'table' => '{table}_Contact',
			'foreign_key' => 'Contact_Tag',
			'far_key' => 'Contact'
		)
	);

	public $column_types = array(
		"id" => self::type_integer,
		"user" => self::type_object,
		"name" => self::type_string
	);

	public $find_keys = array(
		"user",
		"name"
	);
}

