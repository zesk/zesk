<?php declare(strict_types=1);
namespace zesk;

class Class_Contact_Date extends Class_Contact_Info {
	/**
	 *
	 * @var string
	 */
	public $contact_object_field = 'date';

	/**
	 *
	 * @var string
	 */
	public string $id_column = 'id';

	/**
	 *
	 * @var array
	 */
	public array $column_types = [
		'id' => self::type_id,
		'contact' => self::type_object,
		'label' => self::type_object,
		'value' => self::type_string,
		'created' => self::type_created,
		'modified' => self::type_modified,
	];
}
