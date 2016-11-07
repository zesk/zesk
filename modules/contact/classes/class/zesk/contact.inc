<?php

class Class_Zesk_Contact extends zesk\Class_Object {
	public $id_column = "id";

	public $has_many = array(
		"tags" => array(
			'class' => 'Contact_Tag',
			'table' => true // Use Contact_Tag's concept of the table
		),
		"emails" => array(
			'class' => 'Contact_Email'
		),
		"phones" => array(
			'class' => 'Contact_Phone'
		),
		"addresses" => array(
			'class' => 'Contact_Address'
		),
		"urls" => array(
			'class' => 'Contact_URL'
		),
		"dates" => array(
			'class' => 'Contact_Date'
		),
		"others" => array(
			'class' => 'Contact_Other'
		),
		'accounts' => array(
			'class' => 'Account',
			'table' => true,
			'far_key' => 'Account',
			'foreign_key' => 'Contact'
		)
	);

	public $has_one = array(
		"email" => 'Contact_Email',
		"phone" => 'Contact_Phone',
		"address" => 'Contact_Address',
		"url" => 'Contact_URL',
		"person" => 'Contact_Person',
		"account" => 'Account'
	);

}
