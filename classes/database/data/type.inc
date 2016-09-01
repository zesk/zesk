<?php

use zesk\Timestamp as Timestamp;

abstract class Database_Data_Type {

	/**
	 *
	 * @var Database
	 */
	public $database = null;

	/**
	 * Object references
	 * @var string
	 */
	const sql_type_id = "id";
	/**
	 * Text
	 * @var string
	 */
	const sql_type_string = "string";
	/**
	 * Numbers of varying integer precisions
	 * @var string
	 */
	const sql_type_integer = "integer";
	/**
	 * Floating point double precision numbers
	 * @var string
	 */
	const sql_type_double = "double";
	/**
	 * Date
	 * @var string
	 */
	const sql_type_date = "date";
	/**
	 * Time
	 * @var string
	 */
	const sql_type_time = "time";
	/**
	 * Timestamp
	 * @var string
	 */
	const sql_type_datetime = "datetime";
	/**
	 * Large binary data
	 * @var string
	 */
	const sql_type_blob = "blob";
	/**
	 * Large text data
	 * @var unknown
	 */
	const sql_type_text = "text";

	/**
	 * Construct Database_Data_Type
	 * @param Database $database
	 */
	public function __construct(Database $database) {
		$this->database = $database;
	}

	protected $sql_type_natives = array(
		self::sql_type_string => array(
			"char",
			"varchar",
			"text"
		),
		self::sql_type_integer => array(
			self::sql_type_integer,
			'bit'
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
			"datetime"
		)
	);

	protected $sql_type_native_aliases = array(
		'int' => 'integer'
	);
	/**
	 * Override in subclasses to configure alternate native types
	 *
	 * @var array
	 */
	protected $sql_type_to_php_type = array(
		self::sql_type_string => "string",
		self::sql_type_integer => "integer",
		self::sql_type_double => "double",
		self::sql_type_date => "string",
		self::sql_type_time => "string",
		self::sql_type_datetime => "integer"
	);

	protected $pattern_native_type = '/([a-z]+)\(([^)]*)\)/';

	/**
	 *
	 * @param string $type
	 * @return string
	 */
	function native_type_to_data_type($type) {
		return avalue($this->sql_type_to_php_type, $this->native_type_to_sql_type($type, "string"));
	}

	function is_text($native_type) {
		$sql_type = $this->native_type_to_sql_type($native_type);
		return in_array($sql_type, array(
			self::sql_type_string,
			self::sql_type_text
		));
	}

	/**
	 * Override this method to convert the default value to the database canonical default.
	 *
	 * @param string $type
	 *        	sql type
	 * @param mixed $default_value
	 *        	default value supplied
	 * @return mixed Canonical default for this type
	 */
	abstract public function sql_type_default($type, $default_value = null);

	/**
	 * Given a native type, convert default value to the correct type
	 *
	 * @param string $type
	 *        	sql type
	 * @param mixed $default_value
	 *        	default value supplied
	 * @return mixed Canonical default for this type
	 */
	public function native_type_default($type, $default_value = null) {
		return $this->sql_type_default($this->parse_native_type($type), $default_value);
	}

	function parse_sql_type($sql_type, &$size = null) {
		if (empty($sql_type)) {
			return null;
		}
		$matches = false;
		if (!preg_match($this->pattern_native_type, strtolower($sql_type), $matches)) {
			$size = false;
		} else {
			$size = preg_replace('/\s/', '', $matches[2]);
			$sql_type = $matches[1];
		}
		return avalue($this->sql_type_native_aliases, $sql_type, $sql_type);
	}

	/**
	 * Return the standard SQL type for a native type in our database
	 *
	 * @see Database::sql_type_string etc.
	 * @param unknown $native_type
	 * @param string $default
	 */
	final public function native_type_to_sql_type($t, $default = null) {
		$t = $this->parse_sql_type($t);
		foreach ($this->sql_type_natives as $type => $types) {
			if (in_array($t, $types)) {
				return $type;
			}
		}
		return $default;
	}

	/**
	 * Determines if two database-specific data types are compatible and can be altered from one to
	 * the other
	 *
	 * @param string $sqlType0
	 *        	A database-specific data type
	 * @param string $sqlType1
	 *        	A database-specific data type
	 */
	public function native_types_compatible($sql_type0, $sql_type1) {
		$s0 = false;
		$s1 = false;
		$t0 = $this->parse_sql_type($sql_type0, $s0);
		$t1 = $this->parse_sql_type($sql_type1, $s1);

		$bt0 = $this->native_type_to_sql_type($t0);
		$bt1 = $this->native_type_to_sql_type($t1);
		if ($bt0 !== $bt1) {
			return false;
		}
		// Sizes don't matter with integer types
		if ($bt0 !== self::sql_type_integer && $s0 !== $s1) {
			return false;
		}
		if ($t0 === $t1) {
			return true;
		}
		return $this->basic_types_compatible($bt0, $bt1);
	}

	/**
	 * Can I convert a basic SQL type into another in this database?
	 *
	 * @param string $a
	 * @param string $b
	 * @return boolean
	 */
	protected function basic_types_compatible($a, $b) {
		return ($a === $b);
	}

	/**
	 * Do we need to do an ALTER TABLE to make these column types look identical
	 *
	 * @param unknown $native_type0
	 * @param unknown $native_type1
	 * @return boolean
	 */
	public function native_types_equal($native_type0, $native_type1) {
		$s0 = false;
		$s1 = false;
		$t0 = $this->parse_sql_type($native_type0, $s0);
		$t1 = $this->parse_sql_type($native_type1, $s1);
		$bt0 = $this->native_type_to_sql_type($t0);
		$bt1 = $this->native_type_to_sql_type($t1);
		if ($bt0 !== $bt1) {
			return false;
		}
		//log::debug("BASIC TYPE $bt0: $native_type0->$t0 === $native_type1->$t1 ?");
		switch ($bt0) {
			case self::sql_type_date:
				return $t0 === $t1;
			case self::sql_type_datetime:
				return $t0 === $t1;
			case self::sql_type_double:
				return $t0 === $t1 && $s0 === $s1;
			case self::sql_type_id:
				return $t0 === $t1;
			case self::sql_type_integer:
				return $t0 === $t1;
			case self::sql_type_time:
				return $t0 === $t1;
			case self::sql_type_blob:
			case self::sql_type_text:
			case self::sql_type_string:
			default:
				return $t0 === $t1 && $s0 === $s1;
		}
	}

	/*
	 * Type Manipulation
	 */
	/**
	 * Given an internal type and size settings on a database column, generate the database SQL type
	 * for the column. Uses Class_Object::type_foo constants for base type definitions.
	 *
	 * @param Database_Column $type
	 */
	public function type_set_sql_type(Database_Column $type) {
		$type_name = $type->option("type", false);
		$is_bin = $type->option_bool("binary");
		$size = $type->option_integer("size");
		if (!$type_name) {
			throw new Exception_Semantics(__CLASS__ . "::type_set_sql_type(...): \"Type\" is not set! " . print_r($type, true));
		}
		switch (strtolower($type_name)) {
			case Class_Object::type_id:
				$type->set_option("primary_key", true);
				$type->set_option("sql_type", "integer");
				$type->increment(true);
				$type->set_option("unsigned", true);
				return true;
			case Class_Object::type_object:
				$type->set_option("sql_type", "integer");
				$type->set_option("unsigned", true);
				return true;
			case Class_Object::type_integer:
				$type->set_option("sql_type", "integer");
				return true;
			case Class_Object::type_character:
				$size = !is_numeric($size) ? 1 : $size;
				$type->set_option("sql_type", "char($size)");
				return true;
			case Class_Object::type_text:
				$type->set_option("sql_type", "text");
				return true;
			case "varchar":
				zesk::deprecated();
			// fall through
			case Class_Object::type_string:
				if (!is_numeric($size)) {
					$type->set_option("sql_type", $is_bin ? "blob" : "text");
				} else {
					$type->set_option("sql_type", $is_bin ? "varbinary($size)" : "varchar($size)");
				}
				return true;
			case Class_Object::type_boolean:
				$type->set_option("sql_type", "bit(1)");
				return true;
			case "varbinary":
			case Class_Object::type_serialize:
			case Class_Object::type_binary:
				if (!is_numeric($size)) {
					$type->set_option("sql_type", "blob");
				} else {
					$type->set_option("sql_type", "varbinary($size)");
				}
				$type->binary(true);
				return true;
			case Class_Object::type_byte:
				$type->set_option("sql_type", "tinyint(1)");
				$type->set_option("Unsigned", true);
				return true;
			case Class_Object::type_decimal:
				$intP = $type->first_option("integer_precision", 10);
				$decP = $type->first_option("decimal_precision", 2);
				$width = $intP + $decP;
				$type->set_option("sql_type", "decimal($width,$decP)");
				return true;
			case Class_Object::type_real:
				$type->set_option("sql_type", "real");
				return true;
			case Class_Object::type_double:
				$type->set_option("sql_type", "double");
				return true;
			case Class_Object::type_date:
				$type->set_option("sql_type", "date");
				return true;
			case Class_Object::type_time:
				$type->set_option("sql_type", "time");
				return true;
			case Class_Object::type_datetime:
			case Class_Object::type_modified:
			case Class_Object::type_created:
			case Class_Object::type_timestamp:
				$type->set_option("sql_type", "timestamp");
				return true;
			case "checksum":
				zesk::deprecated(); // ?? This used anywhere?
				$type->set_option("sql_type", "char(32)");
				return true;
			case "password":
				zesk::deprecated(); // ?? This used anywhere?
				$type->set_option("sql_type", "varchar(32)");
				return true;
		}
		throw new Exception_Unimplemented(__CLASS__ . "::type_set_sql_type($type_name) unknown");
	}

}