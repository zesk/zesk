<?php declare(strict_types=1);
namespace zesk;

class Class_Contact_Tag_Contact extends Class_ORM {
	public $primary_keys = [
		'contact',
		'contact_tag',
	];

	public $has_one = [
		'contact' => 'zesk\\Contact',
		'contact_tag' => 'zesk\\Contact_Tag',
	];

	public $column_types = [
		'contact' => self::type_object,
		'contact_tag' => self::type_object,
	];
}
