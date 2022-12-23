<?php declare(strict_types=1);
namespace zesk;

class Class_User_Content_Image extends Class_Base {
	public array $primary_keys = [
		'user',
		'image',
	];

	public array $has_one = [
		'user' => "zesk\User",
		'image' => "zesk\Content_Image",
	];

	public array $column_types = [
		'user' => self::type_object,
		'image' => self::type_object,
	];
}
