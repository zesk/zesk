<?php

namespace zesk;

class Class_Contact_Tag extends Class_Contact_Info {
	public $has_one = array(
		'user' => 'zesk\\User'
	);
	public $has_many = array(
		"contact" => array(
			'class' => 'zesk\\Contact',
			'link_class' => 'zesk\\Contact_Tag_Contact',
			'foreign_key' => 'contact_tag',
			'far_key' => 'contact'
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