<?php declare(strict_types=1);
namespace MySQL;

use zesk\Database_Column;
use zesk\Database_Data_Type;
use zesk\Exception_Unimplemented;
use zesk\Exception_Semantics;
use zesk\ORM\Class_Base;

class Database_Type extends Database_Data_Type {
	/**
	 * Override in subclasses to configure alternate native types
	 *
	 * @var array
	 */
	protected array $sql_type_natives = [
		self::sql_type_string => [
			'char',
			'varchar',
		],
		self::sql_type_blob => [
			'blob',
			'longblob',
		],
		self::sql_type_text => [
			'text',
			'longtext',
		],
		self::sql_type_integer => [
			'bit',
			'int',
			'tinyint',
			'smallint',
			'mediumint',
			'bigint',
			'integer',
		],
		self::sql_type_double => [
			'decimal',
		],
		self::sql_type_date => [
			'date',
		],
		self::sql_type_time => [
			'time',
		],
		self::sql_type_datetime => [
			'datetime',
			'timestamp',
		],
	];

	/**
	 * For parsing simple database types. Extracts:
	 *
	 * type as $1
	 * size as $2
	 *
	 * @var string
	 */
	protected string $pattern_native_type = '/([a-z]+)\(([^)]*)\)( unsigned)?/';

	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Database::parse_native_type()
	 */
	final public function parse_native_type(string $sql_type): string {
		$s0 = false;
		$t = $this->parse_sql_type($sql_type, $s0);
		return $this->native_type_to_sql_type($t, $sql_type);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @param string $native_type
	 * @param string $default_value
	 * @return mixed
	 * @see zesk\Database::sql_type_default()
	 */
	public function sql_type_default(string $native_type, mixed $default_value = null): mixed {
		$type = $this->native_type_to_sql_type($native_type, $native_type);
		switch ($type) {
			case self::sql_type_string:
				return strval($default_value);
			case self::sql_type_blob:
			case self::sql_type_text:
				return null;
			case self::sql_type_integer:
				return is_numeric($default_value) ? intval($default_value) : null;
			case self::sql_type_double:
				return is_numeric($default_value) ? floatval($default_value) : null;
			case self::sql_type_datetime:
				if ($default_value === 0 || $default_value === '0') {
					$invalid_dates_ok = $this->database->optionBool('invalid_dates_ok');
					return $invalid_dates_ok ? '0000-00-00 00:00:00' : 'CURRENT_TIMESTAMP';
				}
				return strval($default_value);
		}
		return $default_value;
	}
}
