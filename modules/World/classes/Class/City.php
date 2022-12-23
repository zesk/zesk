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
		'id' => self::TYPE_ID,
		'province' => self::TYPE_OBJECT,
		'county' => self::TYPE_OBJECT,
		'name' => self::TYPE_STRING,
	];
}
