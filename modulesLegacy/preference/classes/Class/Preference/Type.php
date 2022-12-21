<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage user
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 * @see Class_Preference
 * @see Preference_Type
 * @see Preference
 */
class Class_Preference_Type extends Class_Base {
	public string $id_column = 'id';

	public string $name_column = 'name';

	public array $column_types = [
		'id' => self::type_id,
		'code' => self::type_string,
		'name' => self::type_string,
		'description' => self::type_string,
	];

	public array $has_many = [
		'preferences' => [
			'class' => Preference::class,
			'foreign_key' => 'type',
		],
	];

	public array $find_keys = [
		'code',
	];

	public string $database_group = Preference::class;
}
