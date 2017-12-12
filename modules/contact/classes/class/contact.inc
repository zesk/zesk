<?php
namespace zesk;

class Class_Contact extends Class_ORM {
	public $id_column = "id";
	public $column_types = array(
		"id" => self::type_id
	);
	public $has_many = array(
		"tags" => array(
			'class' => 'zesk\\Contact_Tag',
			'link_class' => 'zesk\\Contact_Tag_Contact',
			'far_key' => 'contact_tag',
			'foreign_key' => 'contact'
		),
		"emails" => array(
			'class' => 'zesk\\Contact_Email',
			'foreign_key' => 'contact'
		),
		"phones" => array(
			'class' => 'zesk\\Contact_Phone',
			'foreign_key' => 'contact'
		),
		"addresses" => array(
			'class' => 'zesk\\Contact_Address',
			'foreign_key' => 'contact'
		),
		"urls" => array(
			'class' => 'zesk\\Contact_URL',
			'foreign_key' => 'contact'
		),
		"dates" => array(
			'class' => 'zesk\\Contact_Date',
			'foreign_key' => 'contact'
		),
		"others" => array(
			'class' => 'zesk\\Contact_Other',
			'foreign_key' => 'contact'
		)
	);
	public $has_one = array(
		"email" => 'zesk\\Contact_Email',
		"phone" => 'zesk\\Contact_Phone',
		"address" => 'zesk\\Contact_Address',
		"url" => 'zesk\\Contact_URL',
		"person" => 'zesk\\Contact_Person',
		"account" => 'zesk\\Account'
	);
}
