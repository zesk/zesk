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
		'id' => self::type_id,
		'bank_country' => self::type_object,
		'name' => self::type_string,
		'code' => self::type_string,
		'symbol' => self::type_string,
		'fractional' => self::type_integer,
		'fractional_units' => self::type_string,
		'format' => self::type_string,
		'precision' => self::type_integer,
	];

	public array $column_defaults = [
		'precision' => 2,
	];
}
