<?php declare(strict_types=1);
namespace zesk;

class Class_Contact extends Class_ORM {
	public $id_column = "id";

	public $column_types = [
		"id" => self::type_id,
	];

	public $has_many = [
		"tags" => [
			'class' => 'zesk\\Contact_Tag',
			'link_class' => 'zesk\\Contact_Tag_Contact',
			'far_key' => 'contact_tag',
			'foreign_key' => 'contact',
		],
		"emails" => [
			'class' => 'zesk\\Contact_Email',
			'foreign_key' => 'contact',
		],
		"phones" => [
			'class' => 'zesk\\Contact_Phone',
			'foreign_key' => 'contact',
		],
		"addresses" => [
			'class' => 'zesk\\Contact_Address',
			'foreign_key' => 'contact',
		],
		"urls" => [
			'class' => 'zesk\\Contact_URL',
			'foreign_key' => 'contact',
		],
		"dates" => [
			'class' => 'zesk\\Contact_Date',
			'foreign_key' => 'contact',
		],
		"others" => [
			'class' => 'zesk\\Contact_Other',
			'foreign_key' => 'contact',
		],
	];

	public $has_one = [
		"email" => 'zesk\\Contact_Email',
		"phone" => 'zesk\\Contact_Phone',
		"address" => 'zesk\\Contact_Address',
		"url" => 'zesk\\Contact_URL',
		"person" => 'zesk\\Contact_Person',
		"account" => 'zesk\\Account',
	];
}
