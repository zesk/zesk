<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage tools
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */

namespace zesk;

/**
 * CSV_Writer
 *
 * Long description
 *
 * @package zesk
 * @subpackage tools
 */
class CSV_Writer extends CSV {
	/**
	 * Whether the headers have been written yet
	 *
	 * @var boolean
	 */
	protected bool $WroteHeaders;

	/**
	 * An array containing "map_name" => array("input_column" => "header_index", "input_column2" =>
	 * "header_index2")
	 *
	 * where "map_name" is the name of the map
	 * where "input_column" is the key for an incoming row to be written
	 * and "header_index" is an integer or array of integers where the value should be placed in the
	 * csv row
	 *
	 * @var array
	 */
	protected array $WriteMapGroup;

	/**
	 * Default values for the write maps
	 * Form is: "map_name" => array("input_column" => "default_value", "input_column2" =>
	 * "default_value2", etc.)
	 *
	 * @var array
	 */
	protected array $WriteMapGroupDefault;

	/**
	 * Translation tables for output
	 * Form is: "index" => translation array (old => new)
	 *
	 * @var array
	 */
	protected array $WriteTranslationMap;

	/**
	 * Hooks to call on name/value pair row before writing
	 *
	 * @var array of callable
	 */
	protected array $write_hooks = [];

	/*====================================================================================*\
	 Instance
	 \*------------------------------------------------------------------------------------*/

	/**
	 * Create a new CSV_Writer
	 *
	 * @param array $options
	 */
	public function __construct(array $options = []) {
		parent::__construct($options);
		$this->WroteHeaders = false;
		$this->WriteMapGroup = [];
		$this->WriteMapGroupDefault = [];
		$this->WriteTranslationMap = [];
	}

	/*====================================================================================*\
	 CSV
	 \*------------------------------------------------------------------------------------*/

	/**
	 * Set the file to write to
	 *
	 * @param string $f
	 *            File name to write to
	 * @return boolean If file is opened successfully.
	 */
	public function file($f = null) {
		if ($f === null) {
			return $this->FileName;
		}
		return parent::_set_file($f, "w", true);
	}

	/*====================================================================================*\
	 * CSV Writing to current row
	 \*------------------------------------------------------------------------------------*/

	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
	private function _writeNewRow() {
		return array_fill(0, count($this->Headers), "");
	}

	/**
	 * Add a mapping from an object member names to CSV file header names.
	 *
	 * The values passed in must exist as headers in the CSV file, otherwise an Exception_Key is
	 * thrown.
	 *
	 * @param string $name
	 * @param array $map
	 * @param array $defaultMap
	 * @return boolean
	 */
	public function add_object_map($name, array $map, array $defaultMap = null) {
		if (!is_array($this->HeadersToIndex)) {
			throw new Exception_Semantics("Need to set headers prior to setting a translation map ($name)");
		}
		$this->headers();
		$mapGroup = [];
		foreach ($map as $member => $column) {
			if (!is_string($column)) {
				throw new Exception_Key("Column {column} is not a string", ["column" => $column]);
			}
			$column = strtolower($column);
			if (!isset($this->HeadersToIndex[strtolower($column)])) {
				throw new Exception_Key("{method}({name},...): {column} not found in headers {headers}", ["method" => __METHOD__, "name" => $name, "headers" => JSON::encode($this->HeadersToIndex), "column" => $column, ]);
			} else {
				$indexes = $this->HeadersToIndex[$column];
				$mapGroup[strtolower($member)] = $indexes;
			}
		}
		$this->WriteMapGroup[strtolower($name)] = $mapGroup;
		if (is_array($defaultMap)) {
			$this->WriteMapGroupDefault[strtolower($name)] = array_change_key_case($defaultMap);
		}
		return true;
	}

	/**
	 * Add a translation map for a column which is a boolean value
	 *
	 * @return CSV_Writer
	 */
	public function object_names() {
		return array_keys($this->WriteMapGroup);
	}

	/**
	 * Add an output translation map for boolean values
	 *
	 * @param mixed $column_names
	 *            List of column names
	 * @param string $no
	 *            The no string to output
	 * @param string $yes
	 *            The yes string to output
	 * @param string $null
	 *            The value to output when the value is null
	 * @return CSV_Writer
	 */
	public function add_translation_map_boolean($column_names, $no = null, $yes = null, $null = null) {
		$this->add_translation_map($column_names, ['' => $null === null ? '' : $null, '0' => $no === null ? 'no' : $no, '1' => $yes === null ? 'yes' : $no, ]);
		return $this;
	}

	/**
	 * Set up a mapping of values when writing
	 *
	 * @param string $column_name
	 *            List or single column name
	 * @param array $map
	 *            A list of values to map to and from upon writing
	 * @return CSV_Writer
	 */
	public function add_translation_map($column_names, $map) {
		$column_names = to_list($column_names);
		foreach ($column_names as $column_name) {
			if (!is_array($this->HeadersToIndex)) {
				throw new Exception_Semantics("Need to set headers prior to setting a translation map ($column_name)");
			}
			$column_name = strtolower($column_name);
			$index = avalue($this->HeadersToIndex, $column_name);
			if ($index === null) {
				throw new Exception_Key("CSV_Writer::add_translation_map($column_name, ...) Column not found");
			}
			$this->WriteTranslationMap[$index] = $map;
		}
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @param string $name
	 *            Name of an existing object map
	 * @param array $fields
	 *            ORM to write to row
	 * @return array Written row
	 * @see createORMMap
	 */
	public function set_object($name, $fields) {
		$lowname = strtolower($name);
		if (!isset($this->WriteMapGroup[$lowname])) {
			throw new Exception_Key("CSV::set_object($name) doesn't exist");
		}

		$g = $this->WriteMapGroup[$lowname];

		$fields = array_change_key_case($fields);

		if (isset($this->WriteMapGroupDefault[$lowname])) {
			foreach ($fields as $k => $v) {
				if (!isset($fields[$k]) || ($fields[$k] === "")) {
					$fields[$k] = $v;
				}
			}
		}

		if (!is_array($this->Row)) {
			$this->Row = $this->_writeNewRow();
		}

		foreach ($fields as $k => $v) {
			$k = strtolower($k);
			if (isset($g[$k])) {
				if (is_array($g[$k])) {
					foreach ($g[$k] as $i) {
						$this->Row[$i] = $v;
					}
				} else {
					$this->Row[$g[$k]] = $v;
				}
			}
		}
		return $this->Row;
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $row
	 */
	public function set_row(array $row): void {
		if (count($row) === 0) {
			$this->Row = $row;
			return;
		}
		if (is_array($this->HeadersToIndex)) {
			foreach ($this->write_hooks as $hook) {
				$row = call_user_func($hook, $row);
			}
			foreach ($row as $k => $v) {
				$lowk = strtolower($k);
				$i = avalue($this->HeadersToIndex, $lowk);
				if ($i === null) {
					continue;
				}
				$this->Row[$i] = $v;
			}
			ksort($this->Row);
		} else {
			$this->Row = array_values($row);
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param string $col
	 * @param unknown_type $data
	 * @return bool
	 */
	public function set_column(string $col, $data): bool {
		if (empty($col)) {
			return false;
		}
		$i = avalue($this->HeadersToIndex, strtolower($col));
		if ($i === null) {
			return false;
		}
		$this->Row[$i] = $data;
		return true;
	}

	/**
	 */
	public function write_row(): void {
		$this->_check_file();
		if (!is_array($this->Row)) {
			throw new Exception_Semantics("CSV_Writer:writeRow: Must set row values first");
		}
		$headers = $this->headers();
		if (!$this->WroteHeaders && $this->option_bool("write_header", true) && is_array($headers)) {
			fwrite($this->File, $this->_formatRow($headers));
			$this->RowIndex = 0;
			$this->WroteHeaders = true;
		}
		foreach ($this->WriteTranslationMap as $k => $v) {
			$values = to_list(strval(avalue($this->Row, $k, '')));
			$result = [];
			foreach ($values as $value) {
				$result[] = avalue($v, $value, $value);
			}
			$this->Row[$k] = implode(", ", $result);
		}
		fwrite($this->File, $this->_formatRow($this->Row));
		$this->RowIndex += 1;
		$this->Row = [];
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $row
	 * @return unknown
	 */
	protected function _formatRow($row) {
		$d = $this->Delimiter;
		$e = $this->Enclosure;
		$rowOut = [];
		$pattern = "/[" . preg_quote("$e$d") . '\s]/';
		foreach ($row as $cell) {
			if (preg_match($pattern, $cell)) {
				$cell = $e . str_replace($e, "$e$e", $cell) . $e;
			}
			$rowOut[] = $cell;
		}
		return implode($d, $rowOut) . "\n";
	}

	/**
	 *
	 * @param callable $callable
	 * @return CSV_Writer
	 */
	public function add_write_hook($callable) {
		$name = Hooks::callable_string($callable);
		$this->write_hooks[$name] = $callable;
		return $this;
	}
}
