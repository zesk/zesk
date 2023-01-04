<?php declare(strict_types=1);
namespace zesk;

class Class_Contact_Tag extends Class_Contact_Info {
	public array $has_one = [
		'user' => 'zesk\\User',
	];

	public array $has_many = [
		'contact' => [
			'class' => 'zesk\\Contact',
			'link_class' => 'zesk\\Contact_Tag_Contact',
			'foreign_key' => 'contact_tag',
			'far_key' => 'contact',
		],
	];

	public array $column_types = [
		'id' => self::TYPE_INTEGER,
		'user' => self::TYPE_OBJECT,
		'name' => self::TYPE_STRING,
	];

	public array $find_keys = [
		'user',
		'name',
	];
}
