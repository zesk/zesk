<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage user
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Preference;

use zesk\ORM\Class_Base;

/**
 *
 * @author kent
 * @see Class_Value
 * @see Type
 * @see Value
 */
class Class_Type extends Class_Base
{
	public string $code_name = 'PreferenceType';

	public string $id_column = 'id';

	public string $name_column = 'name';

	public array $column_types = [
		'id' => self::TYPE_ID,
		'code' => self::TYPE_STRING,
		'name' => self::TYPE_STRING,
		'description' => self::TYPE_STRING,
	];

	public array $has_many = [
		'preferences' => [
			'class' => Value::class,
			'foreign_key' => 'type',
		],
	];

	public array $find_keys = [
		'code',
	];

	public string $database_group = Value::class;
}
