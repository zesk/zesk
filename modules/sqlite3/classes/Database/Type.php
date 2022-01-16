<?php declare(strict_types=1);
/**
 *
 */
namespace sqlite3;

/**
 *
 */
use zesk\Database_Column;
use zesk\Class_ORM;
use zesk\Exception_Semantics;

/**
 *
 * @author kent
 *
 */
class Database_Type extends \zesk\Database_Data_Type {
	/**
	 * Override in subclasses to configure alternate native types
	 *
	 * @var unknown
	 */
	protected $sql_type_natives = [
		self::sql_type_string => [
			"char",
			"varchar",
			"text",
		],
		self::sql_type_integer => [
			"bit",
			"int",
			"tinyint",
			"smallint",
			"mediumint",
			"bigint",
			"integer",
		],
		self::sql_type_double => [
			"decimal",
		],
		self::sql_type_date => [
			"date",
		],
		self::sql_type_time => [
			"time",
		],
		self::sql_type_datetime => [
			"datetime",
			"timestamp",
		],
	];

	/**
	 * For parsing simple database types.
	 * Extracts:
	 *
	 * type as $1
	 * size as $2
	 *
	 * @var string
	 */
	protected $pattern_native_type = '/([a-z]+)\(([^)]*)\)( unsigned)?/';

	/*
	 * Type Manipulation Class_ORM::type_foo conversion to SQL Type
	 */
	public function type_set_sql_type(Database_Column $type) {
		$type_name = $type->option("type", false);
		if (!$type_name) {
			throw new Exception_Semantics("{class}::type_set_sql_type(...): \"Type\" is not set! {type}", [
				'class' => get_class($this),
				'type' => _dump($type),
			]);
		}
		$is_bin = $type->optionBool("binary");
		$size = $type->optionInt("size");
		switch (strtolower($type_name)) {
			case Class_ORM::type_integer:
				switch ($size) {
					case 1:
						$type->setOption("sql_type", "tinyint");
						return true;
					case 2:
						$type->setOption("sql_type", "smallint");
						return true;
					case 3:
						$type->setOption("sql_type", "mediumint");
						return true;
					case 4:
						$type->setOption("sql_type", "bigint");
						return true;
					default:
						$type->setOption("sql_type", "integer");
						return true;
				}
				// no break
			case Class_ORM::type_boolean:
				$type->setOption("sql_type", "tinyint");
				return true;
			default:
				return parent::type_set_sql_type($type);
		}
	}

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
	 * @see zesk\Database::sql_type_default()
	 */
	public function sql_type_default($type, $default_value = null) {
		//echo "sql_type_default($type, "._dump($default_value) . ")\n";
		$newtype = $this->native_type_to_sql_type($type, $type);
		//echo "$newtype = $this->native_type_to_sql_type($type, $type)\n";
		$type = $newtype;
		switch ($type) {
			case self::sql_type_string:
				return strval($default_value);
			case self::sql_type_blob:
			case self::sql_type_text:
				return null;
			case self::sql_type_integer:
				return intval($default_value);
			case self::sql_type_double:
				return floatval($default_value);
				return to_bool($default_value, false);
			case self::sql_type_datetime:
				if ($default_value === 0 || $default_value === "0") {
					return '0000-00-00 00:00:00';
				}
				return strval($default_value);
		}
		return $default_value;
	}
}
