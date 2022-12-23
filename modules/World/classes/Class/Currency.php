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
 * Class_Currency
 */
class Class_Currency extends Class_Base {
	public string $id_column = 'id';

	public array $find_keys = [
		'bank_country',
		'code',
	];

	public string $text_column = 'name';

	public string $name = 'Currency';

	public array $has_one = [
		'bank_country' => 'zesk\\Country',
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'bank_country' => self::TYPE_OBJECT,
		'name' => self::TYPE_STRING,
		'code' => self::TYPE_STRING,
		'symbol' => self::TYPE_STRING,
		'fractional' => self::TYPE_INTEGER,
		'fractional_units' => self::TYPE_STRING,
		'format' => self::TYPE_STRING,
		'precision' => self::TYPE_INTEGER,
	];

	public array $column_defaults = [
		'precision' => 2,
	];
}
