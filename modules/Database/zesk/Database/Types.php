<?php
declare(strict_types=1);

namespace zesk\Database;

abstract class Types {
	/**
	 *
	 * @var Base
	 */
	public Base $database;

	/**
	 * ORM references?
	 *
	 * @var string
	 */
	public const SQL_TYPE_ID = 'id';

	/**
	 * Text
	 */
	public const SQL_TYPE_STRING = 'string';

	/**
	 * Numbers of varying integer precisions
	 */
	public const SQL_TYPE_INTEGER = 'integer';

	/**
	 * Floating point double precision numbers
	 */
	public const SQL_TYPE_DOUBLE = 'double';

	/**
	 * Date
	 */
	public const SQL_TYPE_DATE = 'date';

	/**
	 * Time
	 */
	public const SQL_TYPE_TIME = 'time';

	/**
	 * Timestamp
	 */
	public const SQL_TYPE_DATETIME = 'datetime';

	/**
	 * Large binary data
	 */
	public const SQL_TYPE_BLOB = 'blob';

	/**
	 * Large text data
	 */
	public const SQL_TYPE_TEXT = 'text';

	/**
	 * Construct Database_Types
	 * @param Base $database
	 */
	public function __construct(Base $database) {
		$this->database = $database;
	}

	/**
	 * Is the specification for the subclass of SQL_TYPE to native types (not parameterized)
	 *
	 * @var array
	 */
	protected array $sql_type_natives = [
		self::SQL_TYPE_STRING => [
			'char',
			'varchar',
			'text',
		],
		self::SQL_TYPE_INTEGER => [
			self::SQL_TYPE_INTEGER,
			'bit',
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
		],
	];

	/**
	 * Mapping of aliases to sql types
	 *
	 * @var array
	 */
	protected array $sql_type_native_aliases = [
		'int' => self::SQL_TYPE_INTEGER,
	];

	/**
	 * Override in subclasses to configure alternate native types
	 *
	 * @var array
	 */
	protected array $sql_type_to_php_type = [
		self::SQL_TYPE_STRING => 'string',
		self::SQL_TYPE_INTEGER => 'integer',
		self::SQL_TYPE_DOUBLE => 'double',
		self::SQL_TYPE_DATE => 'string',
		self::SQL_TYPE_TIME => 'string',
		self::SQL_TYPE_DATETIME => 'integer',
	];

	protected string $pattern_native_type = '/([a-z]+)\(([^)]*)\)/';

	/**
	 *
	 * @param string $type
	 * @return string
	 */
	public function native_type_to_data_type(string $type): string {
		return $this->sql_type_to_php_type[$this->native_type_to_sql_type($type, 'string')] ?? '';
	}

	public function is_text(string $native_type): bool {
		$sql_type = $this->native_type_to_sql_type($native_type, '');
		return in_array($sql_type, [
			self::SQL_TYPE_STRING,
			self::SQL_TYPE_TEXT,
		]);
	}

	/**
	 * Override this method to convert the default value to the database canonical default.
	 *
	 * @param string $native_type
	 *            sql type
	 * @param string|int|null $default_value
	 *            default value supplied
	 * @return string|int|float|null Canonical default for this type
	 */
	abstract public function sql_type_default(string $native_type, string|int|null $default_value = null): string|int|float|null;

	/**
	 * Given a native type, convert default value to the correct type
	 *
	 * @param string $type sql type
	 * @param string|int|float|null $default_value  default value supplied
	 * @return string|int|float|null Canonical default for this type
	 */
	public function native_type_default(string $type, string|int|float|null $default_value = null): string|int|float|null {
		return $this->sql_type_default($this->parse_native_type($type), $default_value);
	}

	public function parseSQLType($sql_type, &$size = null) {
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
		$sql_type = strtolower($sql_type);
		return $this->sql_type_native_aliases[$sql_type] ?? $sql_type;
	}

	/**
	 * Return the standard SQL type for a native type in our database
	 *
	 * @param string $native_type
	 * @param string $default
	 * @see Database::sql_type_string etc.
	 */
	final public function native_type_to_sql_type(string $native_type, string $default): string {
		$t = $this->parseSQLType($native_type);
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
	 *            A database-specific data type
	 * @param string $sqlType1
	 *            A database-specific data type
	 */
	public function native_types_compatible(string $sql_type0, string $sql_type1): bool {
		$s0 = 0;
		$s1 = 0;
		$t0 = $this->parseSQLType($sql_type0, $s0);
		$t1 = $this->parseSQLType($sql_type1, $s1);

		$bt0 = $this->native_type_to_sql_type($t0);
		$bt1 = $this->native_type_to_sql_type($t1);
		if ($bt0 !== $bt1) {
			return false;
		}
		// Sizes don't matter with integer types
		if ($bt0 !== self::SQL_TYPE_INTEGER && $s0 !== $s1) {
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
	protected function basic_types_compatible(string $a, string $b): bool {
		return strcasecmp($a, $b) === 0;
	}

	/**
	 * Do we need to do an ALTER TABLE to make these column types look identical
	 *
	 * @param string $native_type0
	 * @param string $native_type1
	 * @return boolean
	 */
	public function native_types_equal(string $native_type0, string $native_type1): bool {
		$s0 = false;
		$s1 = false;
		$t0 = $this->parseSQLType($native_type0, $s0);
		$t1 = $this->parseSQLType($native_type1, $s1);
		$bt0 = $this->native_type_to_sql_type($t0, $t0);
		$bt1 = $this->native_type_to_sql_type($t1, $t1);
		if ($bt0 !== $bt1) {
			return false;
		}
		return match ($bt0) {
			self::SQL_TYPE_DATETIME, self::SQL_TYPE_ID, self::SQL_TYPE_INTEGER, self::SQL_TYPE_TIME, self::SQL_TYPE_DATE => $t0 === $t1,
			default => $t0 === $t1 && $s0 === $s1,
		};
	}
}
