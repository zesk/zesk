<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

class Class_Time_Zone extends Class_Base {
	public string $table = 'time_zone_name';

	/**
	 * @var string
	 */
	public string $database_group = 'mysql';

	/**
	 * @var string
	 */
	public string $id_column = 'Time_zone_id';

	public array $find_keys = [
		'Name',
	];

	public array $column_types = [
		'Name' => self::TYPE_STRING,
		'Time_zone_id' => self::TYPE_STRING,
	];
}
