<?php

class Class_Help extends Class_Object {
	public $id_column = 'id';

	public $find_keys = array(
		'target'
	);

	public $column_types = array(
		'id' => self::type_id,
		'target' => self::type_string,
		'type' => self::type_string,
		'title' => self::type_string,
		'placement' => self::type_string,
		'content' => self::type_string,
		'content_url' => self::type_string,
		'require_user' => self::type_integer,
		'active' => self::type_boolean,
		'created' => self::type_created,
		'modified' => self::type_modified,
		'show_first' => self::type_timestamp,
		'show_recent' => self::type_timestamp,
		'show_count' => self::type_integer
	);

	public $column_defaults = array(
		'type' => 'basic',
		'require_user' => true,
		'active' => true,
		'show_count' => 0,
		'content_url' => '',
		'placement' => 'auto'
	);

	public $has_many = array(
		'users' => array(
			'class' => 'Help_User',
			'foreign_key' => 'help',
			'far_key' => 'user'
		)
	);
}