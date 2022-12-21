<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

/**
 *
 * @author kent
 * Copyright &copy; 2022, Market Acumen, Inc.
 */
class Class_Province extends Class_Base {
	public string $id_column = 'id';

	public string $name = 'Province:=State';

	public array $column_types = [
		'id' => self::type_id,
		'country' => self::type_object,
		'code' => self::type_string,
		'name' => self::type_string,
	];

	public array $find_keys = [
		'country',
		'name',
	];

	public array $has_one = [
		'country' => Country::class,
	];
}
