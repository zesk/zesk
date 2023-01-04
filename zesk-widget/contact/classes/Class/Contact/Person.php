<?php declare(strict_types=1);
namespace zesk;

class Class_Contact_Person extends Class_Contact_Info {
	public $contact_object_field = 'Person';

	public string $id_column = 'id';

	public array $column_types = [
		'id' => self::TYPE_ID,
		'contact' => self::TYPE_OBJECT,
		'label' => self::TYPE_OBJECT,
		'name_prefix' => self::TYPE_STRING,
		'name_first' => self::TYPE_STRING,
		'name_middle' => self::TYPE_STRING,
		'name_last' => self::TYPE_STRING,
		'name_suffix' => self::TYPE_STRING,
		'name_nick' => self::TYPE_STRING,
		'name_maiden' => self::TYPE_STRING,
		'title' => self::TYPE_STRING,
		'company' => self::TYPE_OBJECT,
		'gender' => self::TYPE_INTEGER,
		'spouse' => self::TYPE_STRING,
		'children' => self::TYPE_STRING,
		'created' => self::TYPE_CREATED,
		'modified' => self::TYPE_MODIFIED,
	];
}
