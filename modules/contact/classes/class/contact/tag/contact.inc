<?php
namespace zesk;

class Class_Contact_Tag_Contact extends Class_Object {
	public $primary_keys = array(
		'contact',
		'contact_tag'
	);
	public $has_one = array(
		'contact' => 'zesk\\Contact',
		'contact_tag' => 'zesk\\Contact_Tag'
	);
	public $column_types = array(
		'contact' => self::type_object,
		'contact_tag' => self::type_object
	);
}