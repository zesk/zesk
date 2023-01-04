<?php declare(strict_types=1);
namespace zesk;

class Class_Contact_Company extends Class_Contact_Info {
	public $contact_object_field = 'company';

	public string $id_column = 'id';

	public array $column_types = [
		'id' => self::TYPE_ID,
		'name' => self::TYPE_STRING,
		'code' => self::TYPE_STRING,
		'description' => self::TYPE_STRING,
		'logo' => self::TYPE_OBJECT,
		'tax_id' => self::TYPE_STRING,
		'address' => self::TYPE_OBJECT,
		'created' => self::TYPE_CREATED,
		'modified' => self::TYPE_MODIFIED,
	];
}
