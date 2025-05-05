<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage tools
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\CSV;

use zesk\Application\Hooks;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ProtocolException;
use zesk\JSON;
use zesk\Types;

/**
 * CSV_Writer
 *
 * Long description
 *
 * @package    zesk
 * @subpackage tools
 */
class Writer extends Base {
	
	/**
	 * Whether the headers have been written yet
	 *
	 * @var boolean
	 */
	protected bool $writeHeaders = false;
	
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
	protected array $writeMapGroup = [];
	
	/**
	 * Default values for the write maps
	 * Form is: "map_name" => array("input_column" => "default_value", "input_column2" =>
	 * "default_value2", etc.)
	 *
	 * @var array
	 */
	protected array $writeMapGroupDefaults = [];
	
	/**
	 * Translation tables for output
	 * Form is: "index" => translation array (old => new)
	 *
	 * @var array
	 */
	protected array $writeTranslationMap = [];
	
	/**
	 * Hooks to call on name/value pair row before writing
	 *
	 * @var array of callable
	 */
	protected array $writeHooks = [];
	
	/**
	 * Get the filename to write to
	 */
	public function file(): string
	{
		return $this->file;
	}
	
	/**
	 * @param string $f
	 * @return self
	 * @throws FileNotFound
	 * @throws FilePermission
	 */
	public function setFile(string $f): self
	{
		return parent::_setFile($f, 'w', true);
	}
	
	/*====================================================================================*\
	 * CSV Writing to current row
	 \*------------------------------------------------------------------------------------*/
	
	/**
	 * New empty row
	 *
	 * @return array
	 */
	private function _writeNewRow(): array
	{
		return array_fill(0, count($this->headers), '');
	}
	
	/**
	 * Add a mapping from an object member names to CSV file header names.
	 *
	 * The values passed in must exist as headers in the CSV file, otherwise an Exception\KeyNotFound is
	 * thrown.
	 *
	 * @param string     $name
	 * @param array      $map
	 * @param array|null $defaultMap
	 * @return self
	 * @throws KeyNotFound
	 * @throws ProtocolException
	 */
	public function add_object_map(string $name, array $map, array $defaultMap = null): self
	{
		if (!count($this->headersToIndex)) {
			throw new ProtocolException("Need to set headers prior to setting a translation map ($name)");
		}
		$this->headers();
		$mapGroup = [];
		foreach ($map as $member => $column) {
			if (!is_string($column)) {
				throw new KeyNotFound('Column {column} is not a string', ['column' => $column]);
			}
			$column = strtolower($column);
			if (!isset($this->headersToIndex[strtolower($column)])) {
				throw new KeyNotFound('{method}({name},...): {column} not found in headers {headers}', [
					'method'  => __METHOD__,
					'name'    => $name,
					'headers' => JSON::encode($this->headersToIndex),
					'column'  => $column,
				]);
			} else {
				$indexes = $this->headersToIndex[$column];
				$mapGroup[strtolower($member)] = $indexes;
			}
		}
		$this->writeMapGroup[strtolower($name)] = $mapGroup;
		if (is_array($defaultMap)) {
			$this->writeMapGroupDefaults[strtolower($name)] = array_change_key_case($defaultMap);
		}
		return $this;
	}
	
	/**
	 * Add a translation map for a column which is a boolean value
	 *
	 * @return array
	 */
	public function object_names(): array
	{
		return array_keys($this->writeMapGroup);
	}
	
	/**
	 * Add an output translation map for boolean values
	 *
	 * @param mixed  $column_names
	 *            List of column names
	 * @param string $no
	 *            The no string to output
	 * @param string $yes
	 *            The yes string to output
	 * @param string $null
	 *            The value to output when the value is null
	 * @return self
	 */
	public function add_translation_map_boolean($column_names, $no = null, $yes = null, $null = null)
	{
		$this->add_translation_map($column_names, [
			''  => $null === null ? '' : $null,
			'0' => $no === null ? 'no' : $no,
			'1' => $yes === null ? 'yes' : $no,
		]);
		return $this;
	}
	
	/**
	 * Set up a mapping of values when writing
	 *
	 * @param array|string $column_names List or single column name
	 * @param array        $map
	 *                                   A list of values to map to and from upon writing
	 * @return self
	 * @throws KeyNotFound
	 * @throws ProtocolException
	 */
	public function add_translation_map(array|string $column_names, array $map): self
	{
		if (!count($this->headersToIndex)) {
			throw new ProtocolException("Need to set headers prior to setting a translation map ($column_names)");
		}
		$column_names = Types::toList($column_names);
		foreach ($column_names as $column_name) {
			$column_name = strtolower($column_name);
			$index = $this->headersToIndex[$column_name] ?? null;
			if ($index === null) {
				throw new KeyNotFound("CSV_Writer::add_translation_map($column_name, ...) Column not found");
			}
			foreach (Types::toList($index) as $index) {
				$this->writeTranslationMap[$index] = $map;
			}
		}
		return $this;
	}
	
	/**
	 *
	 * @param string $name   Name of an existing object map
	 * @param array  $fields ORM to write to row (name/value pairs)
	 * @return array Written row
	 * @throws KeyNotFound
	 * @see createORMMap
	 */
	public function setObject(string $name, array $fields): array
	{
		$lowName = strtolower($name);
		if (!isset($this->writeMapGroup[$lowName])) {
			throw new KeyNotFound("CSV::set_object($name) doesn't exist");
		}
		
		
		$fields = array_change_key_case($fields);
		
		if (is_array($this->writeMapGroupDefaults[$lowName] ?? false)) {
			foreach ($this->writeMapGroupDefaults[$lowName] as $k => $v) {
				$fields[$k] ??= $v;
			}
		}
		
		if (!count($this->row)) {
			$this->row = $this->_writeNewRow();
		}
		
		$g = $this->writeMapGroup[$lowName];
		foreach ($fields as $k => $v) {
			$k = strtolower($k);
			if (isset($g[$k])) {
				if (is_array($g[$k])) {
					foreach ($g[$k] as $i) {
						$this->row[$i] = $v;
					}
				} else {
					$this->row[$g[$k]] = $v;
				}
			}
		}
		return $this->row;
	}
	
	/**
	 * @param array $row
	 * @return void
	 */
	public function setRow(array $row): void
	{
		if (count($row) === 0) {
			$this->row = $row;
			return;
		}
		if (count($this->headersToIndex)) {
			foreach ($this->writeHooks as $hook) {
				$row = call_user_func($hook, $row);
			}
			foreach ($row as $k => $v) {
				$lowKey = strtolower($k);
				$i = $this->headersToIndex[$lowKey] ?? null;
				if ($i === null) {
					continue;
				}
				$this->row[$i] = $v;
			}
			ksort($this->row);
		} else {
			$this->row = array_values($row);
		}
	}
	
	/**
	 * Set a column value for the current row.
	 *
	 * @param string $col
	 * @param mixed  $data
	 * @return bool
	 */
	public function setColumn(string $col, mixed $data): bool
	{
		if (empty($col)) {
			return false;
		}
		$i = $this->headersToIndex[strtolower($col)] ?? null;
		if ($i === null) {
			return false;
		}
		foreach (Types::toList($i) as $i) {
			$this->row[$i] = $data;
		}
		return true;
	}
	
	/**
	 * @return void
	 * @throws ProtocolException
	 */
	public function writeRow(): void
	{
		$this->_check_file();
		if (!count($this->row)) {
			throw new ProtocolException('CSV_Writer:writeRow: Must set row values first');
		}
		$headers = $this->headers();
		if (!$this->writeHeaders && $this->optionBool('write_header', true)) {
			fwrite($this->File, $this->_formatRow($headers));
			$this->rowIndex = 0;
			$this->writeHeaders = true;
		}
		foreach ($this->writeTranslationMap as $k => $v) {
			$values = Types::toList($this->row[$k] ?? '');
			$result = [];
			foreach ($values as $value) {
				$result[] = $v[$value] ?? $value;
			}
			$this->row[$k] = implode(', ', $result);
		}
		fwrite($this->File, $this->_formatRow($this->row));
		$this->rowIndex += 1;
		$this->row = [];
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $row
	 * @return string
	 */
	protected function _formatRow(array $row): string
	{
		$d = $this->delimiter;
		$e = $this->enclosure;
		$rowOut = [];
		$pattern = '/[' . preg_quote("$e$d") . '\s]/';
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
	 * @return self
	 */
	public function addWriteHook(callable $callable): self
	{
		$name = Hooks::callableString($callable);
		$this->writeHooks[$name] = $callable;
		return $this;
	}
}
