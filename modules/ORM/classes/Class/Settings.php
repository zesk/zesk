<?php declare(strict_types=1);
namespace zesk\ORM;

/**
 * @see Settings
 * @author kent
 *
 */
class Class_Settings extends Class_Base {
	public string $id_column = 'name';

	public array $column_types = [
		'name' => self::type_string,
		'value' => self::type_serialize,
		'modified' => self::type_modified,
	];

	/**
	 * No auto column
	 */
	public string $auto_column = '';
}
