<?php
/**
 * Class_Zesk_Contact_Email
 */
class Class_Zesk_Contact_Email extends Class_Contact_Info {

	public $find_keys = array(
		"Value"
	);

	public $has_one = array(
		'Contact' => 'Contact'
	);

	public $column_types = array(
		'verified' => 'timestamp',
		'modified' => 'modified',
		'created' => 'created',
		'opt_out' => 'boolean'
	);

	public $contact_object_field = "Email";
}

