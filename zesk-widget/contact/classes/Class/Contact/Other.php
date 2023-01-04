<?php declare(strict_types=1);
namespace zesk;

class Class_Contact_Other extends Class_Contact_Info {
	public $contact_object_field = 'info';

	public string $id_column = 'id';

	public array $column_types = [
		'id' => self::TYPE_ID,
		'contact' => self::TYPE_OBJECT,
		'label' => self::TYPE_OBJECT,
		'value' => self::TYPE_STRING,
		'created' => self::TYPE_CREATED,
		'modified' => self::TYPE_MODIFIED,
	];
}
