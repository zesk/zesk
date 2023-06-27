<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\MySQL;

use zesk\Database\Types as BaseTypes;

class Types extends BaseTypes
{
	/**
	 * Override in subclasses to configure alternate native types
	 *
	 * @var array
	 */
	protected array $sql_type_natives = [
		self::SQL_TYPE_STRING => [
			'char',
			'varchar',
		],
		self::SQL_TYPE_BLOB => [
			'blob',
			'longblob',
		],
		self::SQL_TYPE_TEXT => [
			'text',
			'longtext',
		],
		self::SQL_TYPE_INTEGER => [
			'bit',
			'int',
			'tinyint',
			'smallint',
			'mediumint',
			'bigint',
			'integer',
		],
		self::SQL_TYPE_DOUBLE => [
			'decimal',
		],
		self::SQL_TYPE_DATE => [
			'date',
		],
		self::SQL_TYPE_TIME => [
			'time',
		],
		self::SQL_TYPE_DATETIME => [
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
	final public function parse_native_type(string $sql_type): string
	{
		$s0 = false;
		$t = $this->parse_sql_type($sql_type, $s0);
		return $this->native_type_to_sql_type($t, $sql_type);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @param string $native_type
	 * @param string|int|float|null $default_value
	 * @return string|int|float|null
	 * @see Database_Types::sql_type_default()
	 */
	public function sql_type_default(string $native_type, string|int|float|null $default_value = null):
	string|int|float|null
	{
		$type = $this->native_type_to_sql_type($native_type, $native_type);
		switch ($type) {
			case self::SQL_TYPE_STRING:
				return strval($default_value);
			case self::SQL_TYPE_BLOB:
			case self::SQL_TYPE_TEXT:
				return null;
			case self::SQL_TYPE_INTEGER:
				return is_numeric($default_value) ? intval($default_value) : null;
			case self::SQL_TYPE_DOUBLE:
				return is_numeric($default_value) ? floatval($default_value) : null;
			case self::SQL_TYPE_DATETIME:
				if ($default_value === 0 || $default_value === '0') {
					$invalid_dates_ok = $this->database->optionBool('invalid_dates_ok');
					return $invalid_dates_ok ? '0000-00-00 00:00:00' : 'CURRENT_TIMESTAMP';
				}
				return strval($default_value);
		}
		return $default_value;
	}
}
