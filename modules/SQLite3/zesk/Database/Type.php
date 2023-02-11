<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\SQLite3;

/**
 *
 */
use zesk\Database_Column;
use zesk\Database_Data_Type;
use zesk\ORM\Class_Base;
use zesk\Exception_Semantics;

/**
 *
 * @author kent
 *
 */
class Database_Type extends Database_Data_Type {
	protected array $sql_type_natives = [
		self::SQL_TYPE_STRING => [
			'char',
			'varchar',
			'text',
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
	 * For parsing simple database types.
	 * Extracts:
	 *
	 * type as $1
	 * size as $2
	 *
	 * @var string
	 */
	protected string $pattern_native_type = '/([a-z]+)\(([^)]*)\)( unsigned)?/';

	/*
	 * Type Manipulation Class_Base::type_foo conversion to SQL Type
	 */
	public function type_set_sql_type(Database_Column $type) {
		$type_name = $type->option('type', false);
		if (!$type_name) {
			throw new Exception_Semantics('{class}::type_set_sql_type(...): "Type" is not set! {type}', [
				'class' => get_class($this),
				'type' => _dump($type),
			]);
		}
		$is_bin = $type->optionBool('binary');
		$size = $type->optionInt('size');
		switch (strtolower($type_name)) {
			case Class_Base::TYPE_INTEGER:
				switch ($size) {
					case 1:
						$type->setOption('sql_type', 'tinyint');
						return true;
					case 2:
						$type->setOption('sql_type', 'smallint');
						return true;
					case 3:
						$type->setOption('sql_type', 'mediumint');
						return true;
					case 4:
						$type->setOption('sql_type', 'bigint');
						return true;
					default:
						$type->setOption('sql_type', 'integer');
						return true;
				}
				// no break
			case Class_Base::TYPE_BOOL:
				$type->setOption('sql_type', 'tinyint');
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
	 * @see Database::sql_type_default()
	 */
	public function sql_type_default(string $type, mixed $default_value = null): float|int|null|string {
		//echo "sql_type_default($type, "._dump($default_value) . ")\n";
		$newtype = $this->native_type_to_sql_type($type, $type);
		//echo "$newtype = $this->native_type_to_sql_type($type, $type)\n";
		$type = $newtype;
		switch ($type) {
			case self::SQL_TYPE_STRING:
				return strval($default_value);
			case self::SQL_TYPE_BLOB:
			case self::SQL_TYPE_TEXT:
				return null;
			case self::SQL_TYPE_INTEGER:
				return intval($default_value);
			case self::SQL_TYPE_DOUBLE:
				return floatval($default_value);
			case self::SQL_TYPE_DATETIME:
				if ($default_value === 0 || $default_value === '0') {
					return '0000-00-00 00:00:00';
				}
				return strval($default_value);
		}
		return $default_value;
	}
}
