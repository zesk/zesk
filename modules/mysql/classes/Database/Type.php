<?php
namespace MySQL;

use zesk\Database_Column;
use zesk\Exception_Unimplemented;
use zesk\Exception_Semantics;
use zesk\Class_ORM;

class Database_Type extends \zesk\Database_Data_Type {
	/**
	 * Override in subclasses to configure alternate native types
	 *
	 * @var unknown
	 */
	protected $sql_type_natives = array(
		self::sql_type_string => array(
			"char",
			"varchar",
		),
		self::sql_type_blob => array(
			"blob",
			"longblob",
		),
		self::sql_type_text => array(
			"text",
			"longtext",
		),
		self::sql_type_integer => array(
			"bit",
			"int",
			"tinyint",
			"smallint",
			"mediumint",
			"bigint",
			"integer",
		),
		self::sql_type_double => array(
			"decimal",
		),
		self::sql_type_date => array(
			"date",
		),
		self::sql_type_time => array(
			"time",
		),
		self::sql_type_datetime => array(
			"datetime",
			"timestamp",
		),
	);

	/**
	 * For parsing simple database types. Extracts:
	 *
	 * type as $1
	 * size as $2
	 *
	 * @var string
	 */
	protected $pattern_native_type = '/([a-z]+)\(([^)]*)\)( unsigned)?/';

	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Database::parse_native_type()
	 */
	final public function parse_native_type($sql_type) {
		$s0 = false;
		$t = $this->parse_sql_type($sql_type, $s0);
		return $this->native_type_to_sql_type($t);
	}

    /**
     * (non-PHPdoc)
     *
     * @param string $native_type
     * @param string $default_value
     * @return string
     * @see zesk\Database::sql_type_default()
     */
	public function sql_type_default($native_type, $default_value = null) {
        $type = $this->native_type_to_sql_type($native_type, $native_type);
		switch ($type) {
			case self::sql_type_string:
				return strval($default_value);
			case self::sql_type_blob:
			case self::sql_type_text:
				return null;
			case self::sql_type_integer:
				return intval($default_value);
			case self::sql_type_double:
				return doubleval($default_value);
			case self::sql_type_datetime:
				if ($default_value === 0 || $default_value === "0") {
					$invalid_dates_ok = $this->database->option_bool("invalid_dates_ok");
					return $invalid_dates_ok ? '0000-00-00 00:00:00' : 'CURRENT_TIMESTAMP';
				}
				return strval($default_value);
		}
		return $default_value;
	}
}
