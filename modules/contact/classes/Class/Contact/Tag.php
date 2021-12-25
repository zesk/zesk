<?php declare(strict_types=1);
namespace zesk;

class Class_Contact_Tag extends Class_Contact_Info {
	public $has_one = [
		'user' => 'zesk\\User',
	];

	public $has_many = [
		"contact" => [
			'class' => 'zesk\\Contact',
			'link_class' => 'zesk\\Contact_Tag_Contact',
			'foreign_key' => 'contact_tag',
			'far_key' => 'contact',
		],
	];

	public $column_types = [
		"id" => self::type_integer,
		"user" => self::type_object,
		"name" => self::type_string,
	];

	public $find_keys = [
		"user",
		"name",
	];
}
