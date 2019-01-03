<?php
namespace zesk;

class Class_Contact_Email extends Class_Contact_Info {
	public $contact_object_field = "email";

	public $find_keys = array(
		"value",
	);

	public $has_one = array(
		'contact' => 'contact',
	);

	public $column_types = array(
		'verified' => 'timestamp',
		'modified' => 'modified',
		'created' => 'created',
		'opt_out' => 'boolean',
	);
}
