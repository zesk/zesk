<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage tools
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\CSV;

use zesk\Exception\FileNotFound;
use zesk\Exception\FileParseException;
use zesk\Exception\FilePermission;
use zesk\Exception\KeyNotFound;
use zesk\Exception\SemanticsException;
use zesk\Timestamp;
use zesk\Types;

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
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws FileParseException
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
	 * @throws FileNotFound
	 * @throws FileParseException
	 * @throws FilePermission
	 */
	public static function factory(string $filename = '', array $options = []): self {
		return new self($filename, $options);
	}

	/**
	 * Set or get a simple mapping for reading objects from a CSV, for example
	 *
	 * Usage:
	 *
	 *        `$reader->read_map("foo", array("nameX" => "name"))` - Sets the read map "foo" to array
	 *        `$reader->read_map("foo")` - Returns the current row formatted using read map "foo"
	 *        `$reader->read_map()` - Returns all read map names associated with this reader (["foo","bar"])
	 *
	 * @param string $name Name of this map, case-insensitive
	 * @param array $map Array of csv_column => new_key - if null then returns named map
	 * @param array $mapTypes
	 * @param array $defaultMap
	 * @return MapReader
	 * @throws KeyNotFound
	 * @throws SemanticsException
	 */
	public function addReadMap(string $name, array $map, array $mapTypes = [], array $defaultMap = []): self {
		if (!count($this->Headers)) {
			throw new SemanticsException('Must have headers before setting map');
		}
		$this->headers();
		$mapGroup = [];
		foreach ($map as $column => $objectMember) {
			$column = strtolower($column);
			if (!isset($this->HeadersToIndex[$column])) {
				throw new KeyNotFound("CSV::readSetMap($name,...): $column not found in headers {headers_to_index}", [
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
		return $this;
	}

	/**
	 * @param string $name
	 * @return array
	 * @throws KeyNotFound
	 * @throws SemanticsException
	 */
	public function readMap(string $name): array {
		if (!count($this->Headers)) {
			throw new SemanticsException('Must have headers before setting map');
		}
		return $this->_getReadMap($name);
	}

	/**
	 * Given a source row $data, and a type map
	 * @param array $row
	 * @param array $typeMap
	 * @param array $defaultMap
	 * @return array
	 * @throws KeyNotFound
	 */
	private static function _applyTypes(array $row, array $typeMap, array $defaultMap): array {
		foreach ($typeMap as $k => $type) {
			$v = $row[$k] ?? null;
			if ($v !== null) {
				if (is_string($type)) {
					switch ($type) {
						case 'boolean':
							$row[$k] = Types::toBool($v);

							break;
						case 'timestamp':
						case 'datetime':
							$row[$k] = Timestamp::factory($v);

							break;
						default:
							throw new KeyNotFound("Unknown type map $type in CSV::_applyTypes");
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
	 * @throws KeyNotFound
	 */
	private function _getReadMap(string $name): array {
		$lowName = strtolower($name);
		if (!isset($this->readMapGroup[$lowName])) {
			throw new KeyNotFound("CSV::readMap($name) doesn't exist");
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
	 * @throws KeyNotFound
	 */
	public function addTranslationMapBoolean(string|array $columns): self {
		return $this->_addTranslationMapType(Types::toList($columns), self::MAP_BOOLEAN);
	}

	/**
	 * Add translation map for a specific type
	 *
	 * @param array $columns
	 * @param string $type Class_Base::type_foo type
	 * @return self
	 * @throws KeyNotFound
	 */
	private function _addTranslationMapType(array $columns, string $type): self {
		foreach ($columns as $column) {
			if (!in_array($column, $this->Headers)) {
				throw new KeyNotFound('Unknown header key {key} in CSV reader for file {file} (type {type})', [
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
	 * @return array
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
	private function _convert_boolean(mixed $value): bool {
		return Types::toBool($value);
	}

	private function _convert_text(mixed $value): string {
		return strval($value);
	}
}
