<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

class Class_County extends Class_Base {
	public string $name = 'County';

	public string $id_column = 'id';

	public string $name_column = 'name';

	public array $has_one = [
		'province' => Province::class,
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'province' => self::TYPE_OBJECT,
		'name' => self::TYPE_STRING,
	];
}
