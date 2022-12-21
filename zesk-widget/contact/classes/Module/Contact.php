<?php declare(strict_types=1);
namespace zesk;

class Module_Contact extends Module {
	protected array $modelClasses = [
		'zesk\\Contact',
		'zesk\\Contact_Label',
		'zesk\\Contact_Address',
		'zesk\\Contact_Company',
		'zesk\\Contact_Date',
		'zesk\\Contact_Other',
		'zesk\\Contact_Person',
		'zesk\\Contact_Phone',
		'zesk\\Contact_Tag',
		'zesk\\Contact_URL',
	];
}
