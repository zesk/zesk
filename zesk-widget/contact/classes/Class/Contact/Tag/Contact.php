<?php declare(strict_types=1);
namespace zesk;

class Class_Contact_Tag_Contact extends Class_Base {
	public array $primary_keys = [
		'contact',
		'contact_tag',
	];

	public array $has_one = [
		'contact' => 'zesk\\Contact',
		'contact_tag' => 'zesk\\Contact_Tag',
	];

	public array $column_types = [
		'contact' => self::TYPE_OBJECT,
		'contact_tag' => self::TYPE_OBJECT,
	];
}
