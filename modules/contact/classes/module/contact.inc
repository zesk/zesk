<?php
namespace zesk;

class Module_Contact extends Module {
	protected $object_classes = array(
		'zesk\\Contact',
		'zesk\\Contact_Label',
		'zesk\\Contact_Address',
		'zesk\\Contact_Company',
		'zesk\\Contact_Date',
		'zesk\\Contact_Other',
		'zesk\\Contact_Person',
		'zesk\\Contact_Phone',
		'zesk\\Contact_Tag',
		'zesk\\Contact_URL'
	);
}
