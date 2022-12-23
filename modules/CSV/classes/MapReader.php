<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage tools
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\CSV;

use zesk\Exception_File_Permission;
use zesk\Exception_File_NotFound;
use zesk\Exception_Key;
use zesk\Exception_Semantics;
use zesk\Timestamp;

/**
 * CSV_Reader_Map
 *
 * Read from a CSV file and convert rows into objects easily
 *
 * @todo Refactor so this works on any array input, not just CSVs
 *
 * @package zesk
 * @subpackage tools
 */
class MapReader extends Reader {
	/**
	 * A read map group allows you to read a subset of a row and map the column names into new names. This is useful when
	 * mapping a CSV row into one or more zesk\ORM subclasses.
	 *
	 * @var array
	 */
	protected array $readMapGroup;

	/**
	 * The read map group defaults provides default values, per read map, used to populate the result when the value does not exist.
	 *
	 * @var array
	 */
	protected array $readMapGroupDefaults;

	/**
	 * The read map group types provides column types, per read map, used to
	 * @todo Move ReadMap functionality to another class, doesn't belong with RAW self functionality
	 *
	 * @var array
	 */
	protected array $readMapGroupTypes;

	/**
	 *
	 * @var array
	 */
	protected array $translationMap = [];

	/**
	 *
	 * @param string $filename
	 * @param array $options
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 */
	public function __construct(string $filename = '', array $options = []) {
		parent::__construct($filename, $options);
		$this->readMapGroup = [];
		$this->readMapGroupDefaults = [];
		$this->readMapGroupTypes = [];
	}

	/**
	 * Create
	 *
	 * @param string $filename
	 * @param array $options
	 * @return self
	 * @throws Exception_File_Permission
	 * @throws Exception_File_NotFound
	 */
	public static function factory(string $filename = '', array $options = []): self {
		return new self($filename, $options);
	}

	/**
	 * Set or get a simple mapping for reading objects from a CSV, for example
	 *
	 * Usage:
	 *
	 *        `$reader->read_map("foo", array("namen" => "name"))` - Sets the read map "foo" to array
	 *        `$reader->read_map("foo")` - Returns the current row formatted using read map "foo"
	 *        `$reader->read_map()` - Returns all read map names associated with this reader (["foo","bar"])
	 *
	 * @param string|null $name Name of this map - case insensitive
	 * @param array|null $map Array of csv_column => new_key - if null then returns named map
	 * @param array|null $mapTypes
	 * @param array|null $defaultMap
	 * @return array
	 * @throws Exception_Semantics
	 * @throws Exception_Key
	 */
	public function addReadMap(string $name, array $map, array $mapTypes = [], array $defaultMap = []): self {
		if (!count($this->Headers)) {
			throw new Exception_Semantics('Must have headers before setting map');
		}
		$this->headers();
		$mapGroup = [];
		foreach ($map as $column => $objectMember) {
			$column = strtolower($column);
			if (!isset($this->HeadersToIndex[$column])) {
				throw new Exception_Key("CSV::readSetMap($name,...): $column not found in headers {headers_to_index}", [
					'headers_to_index' => $this->HeadersToIndex,
				]);
			} else {
				$indexes = $this->HeadersToIndex[$column];
				if (is_array($indexes)) {
					foreach ($indexes as $index) {
						$mapGroup[$index] = $objectMember;
					}
				} else {
					$mapGroup[$indexes] = $objectMember;
				}
			}
		}
		$name = strtolower($name);
		$this->readMapGroup[$name] = $mapGroup;
		if (count($mapTypes)) {
			$this->readMapGroupTypes[$name] = $mapTypes;
		}
		if (count($defaultMap)) {
			$this->readMapGroupDefaults[$name] = $defaultMap;
		}
		return true;
	}

	/**
	 * @param string $name
	 * @return array
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function readMap(string $name): array {
		if (!count($this->Headers)) {
			throw new Exception_Semantics('Must have headers before setting map');
		}
		return $this->_getReadMap($name);
	}

	/**
	 * Given a source row $data, and a type map
	 * @param array $row
	 * @param array $typeMap
	 * @param array $defaultMap
	 * @return array
	 * @throws Exception_Key
	 */
	private static function _applyTypes(array $row, array $typeMap, array $defaultMap): array {
		foreach ($typeMap as $k => $type) {
			$v = $row[$k] ?? null;
			if ($v !== null) {
				if (is_string($type)) {
					switch ($type) {
						case 'boolean':
							$row[$k] = toBool($v);

							break;
						case 'timestamp':
						case 'datetime':
							$row[$k] = Timestamp::factory($v);

							break;
						default:
							throw new Exception_Key("Unknown type map $type in CSV::apply_types");
					}
				} elseif (is_array($type)) {
					$v = $type[$v] ?? $defaultMap[$k] ?? null;
					if ($v !== null) {
						$row[$k] = $v;
					}
				}
			}
		}
		return $row;
	}

	/**
	 * Retrieve the read map of name
	 *
	 * @param string $name
	 * @return array
	 * @throws Exception_Key
	 */
	private function _getReadMap(string $name): array {
		$lowName = strtolower($name);
		if (!isset($this->readMapGroup[$lowName])) {
			throw new Exception_Key("CSV::readMap($name) doesn't exist");
		}
		$g = $this->readMapGroup[$lowName];
		$result = [];
		$r = $this->Row;
		foreach ($g as $index => $cols) {
			$value = $r[$index];
			if (!is_array($cols)) {
				$cols = [
					$cols,
				];
			}
			foreach ($cols as $col) {
				if (isset($result[$col])) {
					$rowColumn = &$result[$col];
					if (is_array($rowColumn)) {
						$rowColumn[] = $value;
					} else {
						$rowColumn = [
							$rowColumn, $value,
						];
					}
				} else {
					$result[$col] = $value;
				}
			}
		}
		if (isset($this->readMapGroupDefaults[$lowName])) {
			foreach ($this->readMapGroupDefaults[$lowName] as $k => $v) {
				if (array_key_exists($k, $result)) {
					continue;
				}
				$result[$k] = $v;
			}
		}
		$t = $this->readMapGroupTypes[$lowName] ?? null;
		if ($t) {
			$result = self::_applyTypes($result, $t, $this->readMapGroupDefaults[$lowName] ?? []);
		}
		return $result;
	}

	public const MAP_BOOLEAN = 'boolean';

	/**
	 * Set columns to be converted to boolean upon reading
	 *
	 * @param string|array $columns
	 * @return self
	 * @throws Exception_Key
	 */
	public function addTranslationMapBoolean(string|array $columns): self {
		return $this->_addTranslationMapType(toList($columns), self::MAP_BOOLEAN);
	}

	/**
	 * Add translation map for a specific type
	 *
	 * @param array $columns
	 * @param string $type Class_Base::type_foo type
	 * @return self
	 * @throws Exception_Key
	 */
	private function _addTranslationMapType(array $columns, string $type): self {
		foreach ($columns as $column) {
			if (!in_array($column, $this->Headers)) {
				throw new Exception_Key('Unknown header key {key} in CSV reader for file {file} (type {type})', [
					'key' => $column, 'type' => $type, 'file' => $this->FileName,
				]);
			}
			$this->translationMap[$column] = $type;
		}
		return $this;
	}

	/**
	 *
	 * @param array $row
	 */
	protected function postProcessRow(array $row): array {
		foreach ($this->translationMap as $column => $type) {
			if (!array_key_exists($column, $row)) {
				continue;
			}
			$method = match ($type) {
				self::MAP_BOOLEAN => $this->_convert_boolean(...),
				default => $this->_convert_text(...),
			};
			$row[$column] = $this->$method($row[$column], $row, $column);
		}
		return $row;
	}

	/**
	 * Convert data to boolean
	 */
	private function _convert_boolean(mixed $value, array $row, $column): bool {
		return toBool($value, false);
	}

	private function _convert_text(mixed $value, array $row, $column): string {
		return strval($value);
	}
}
