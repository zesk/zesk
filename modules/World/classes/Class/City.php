<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

class Class_City extends Class_Base {
	public string $id_column = 'id';

	public array $find_keys = [
		'name',
	];

	public array $has_one = [
		'province' => 'zesk\\Province',
		'county' => 'zesk\\County',
	];

	public array $column_types = [
		'id' => self::type_id,
		'province' => self::type_object,
		'county' => self::type_object,
		'name' => self::type_string,
	];
}
