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
		'name' => self::TYPE_STRING,
		'value' => self::TYPE_SERIALIZE,
		'modified' => self::TYPE_MODIFIED,
	];

	/**
	 * No auto column
	 */
	public string $auto_column = '';
}
