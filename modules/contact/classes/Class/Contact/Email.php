<?php declare(strict_types=1);
namespace zesk;

class Class_Contact_Email extends Class_Contact_Info {
	public $contact_object_field = "email";

	public $find_keys = [
		"value",
	];

	public $has_one = [
		'contact' => 'contact',
	];

	public $column_types = [
		'verified' => 'timestamp',
		'modified' => 'modified',
		'created' => 'created',
		'opt_out' => 'boolean',
	];
}
