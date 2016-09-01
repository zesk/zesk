<?php

namespace mysql;

use \Database_Column as Database_Column;
use \Exception_Unimplemented as Exception_Unimplemented;
use \Exception_Semantics as Exception_Semantics;
use \Class_Object as Class_Object;

class Database_Type extends \Database_Data_Type {
	/**
	 * Override in subclasses to configure alternate native types
	 *
	 * @var unknown
	 */
	protected $sql_type_natives = array(
		self::sql_type_string => array(
			"char",
			"varchar",
			"text"
		),
		self::sql_type_integer => array(
			"bit",
			"int",
			"tinyint",
			"smallint",
			"mediumint",
			"bigint",
			"integer"
		),
		self::sql_type_double => array(
			"decimal"
		),
		self::sql_type_date => array(
			"date"
		),
		self::sql_type_time => array(
			"time"
		),
		self::sql_type_datetime => array(
			"datetime",
			"timestamp"
		)
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
	
	/*
	 * Type Manipulation Internal Type conversion to SQL Type
	 */
	public function type_set_sql_type(Database_Column $type) {
		$typeName = $type->option("type", false);
		$is_bin = $type->option_bool("binary");
		$size = $type->option_integer("size");
		$size = !is_numeric($size) ? 1 : $size;
		if (!$typeName) {
			throw new Exception_Semantics("{class}::type_set_sql_type(...): \"Type\" is not set! " . print_r($type, true), array(
				"class" => get_class($this)
			));
		}
		switch (strtolower($typeName)) {
			case Class_Object::type_character:
				$size = !is_numeric($size) ? 1 : $size;
				$bin_suffix = ($is_bin) ? " BINARY" : "";
				$type->set_option("sql_type", "char($size)$bin_suffix");
				return true;
			case Class_Object::type_boolean:
				$type->set_option("sql_type", "tinyint");
				return true;
			default :
				return parent::type_set_sql_type($type);
		}
		throw new Exception_Unimplemented("{method}($typeName) unknown", array(
			"method" => __METHOD__
		));
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Database::parse_native_type()
	 */
	final function parse_native_type($sql_type) {
		$s0 = false;
		$t = $this->parse_sql_type($sql_type, $s0);
		return $this->native_type_to_sql_type($t);
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Database::sql_type_default()
	 */
	function sql_type_default($type, $default_value = null) {
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
				return doubleval($default_value);
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