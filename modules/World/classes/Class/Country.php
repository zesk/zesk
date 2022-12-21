<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

class Class_Country extends Class_Base {
	public string $id_column = 'id';

	public array $find_keys = [
		'code',
	];

	public array $column_types = [
		'id' => self::type_id,
		'code' => self::type_string,
		'name' => self::type_string,
	];
}
