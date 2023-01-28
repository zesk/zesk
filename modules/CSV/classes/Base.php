<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage tools
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\CSV;

use zesk\Options;
use zesk\StringTools;
use zesk\Exception_File_NotFound;
use zesk\Exception_File_Permission;
use zesk\Exception_Parameter;
use zesk\Exception_Key;
use zesk\Exception_Semantics;

/**
 * CSV
 *
 * Base class for CSV_Reader and CSV_Writer
 *
 * @todo refactor for new naming scheme
 * @package zesk
 * @subpackage system
 */
abstract class Base extends Options {
	/**
	 * @var resource
	 */
	protected mixed $File;

	/**
	 * @var string
	 */
	protected string $FileName;

	/**
	 * @var string
	 */
	protected string $Delimiter;

	/**
	 * @var string
	 */
	protected string $Enclosure;

	/**
	 * @var string
	 */
	protected string $Escape;

	/**
	 * @var string
	 */
	protected string $LineDelimiter;

	/**
	 * @var array
	 */
	protected array $Headers = [];

	/**
	 * @var array
	 */
	protected array $HeadersToIndex = [];

	/**
	 * @var integer
	 */
	protected int $RowIndex;

	/**
	 * current row being read or written
	 * @var array
	 */
	protected array $Row;

	/**
	 * File encoding
	 * @var string
	 */
	protected string $Encoding = 'UTF-8';

	/**
	 *
	 * @var string
	 */
	protected string $EncodingSuffix = '.UTF8';

	/**
	 *
	 * @var bool
	 */
	protected bool $EncodingBigEndian = true;

	/**
	 * Create new CSV
	 *
	 * @param mixed $options Array of options
	 */
	public function __construct(array $options = []) {
		parent::__construct($options);

		$this->RowIndex = 0;
		$this->File = false;
		$this->Delimiter = ',';
		$this->Enclosure = '"';
		$this->Escape = '\\';
		$this->LineDelimiter = "\n";
	}

	/**
	 * Set the file associated with this CSV object
	 *
	 * @param string $filename File path to use
	 * @param string $mode File mode (fopen)
	 * @param boolean $create Create the file if it doesn't exist
	 * @return self
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 * @see fopen
	 */
	protected function _setFile(string $filename, string $mode, bool $create = false): self {
		if (!$create && !file_exists($filename)) {
			throw new Exception_File_NotFound($filename);
		}
		$this->File = fopen($filename, $mode);
		if (!$this->File) {
			throw new Exception_File_Permission("Can't open $filename with mode $mode");
		}

		$this->FileName = $filename;
		$this->Row = [];
		$this->RowIndex = 0;
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @return void
	 */
	public function close(): void {
		if (is_resource($this->File)) {
			fclose($this->File);
			$this->File = null;
		}
	}

	/**
	 * Add headers to the CSV file
	 *
	 * @param array $headers
	 * @param boolean $is_map The passed in array is a map from internal name => label name
	 * @return Base
	 */
	public function add_headers(array $headers, bool $is_map = true): self {
		if ($is_map) {
			foreach ($headers as $column => $header_name) {
				$index = count($this->Headers);
				$this->Headers[$index] = $header_name;
				$this->HeadersToIndex[strtolower($column)] = $index;
			}
		} else {
			foreach ($headers as $mixed) {
				$index = count($this->Headers);
				$this->Headers[$index] = $mixed;
				if (!is_array($mixed)) {
					$mixed = [
						$mixed,
					];
				}
				foreach ($mixed as $name) {
					if (!is_string($name)) {
						throw new Exception_Key('Invalid key {name}');
					}
					$lowName = strtolower($name);
					if (array_key_exists($lowName, $this->HeadersToIndex)) {
						$this->HeadersToIndex[$lowName][] = $index;
					} else {
						$this->HeadersToIndex[$lowName] = [$index];
					}
				}
			}
		}
		return $this;
	}

	/**
	 * @param array $headers
	 * @param bool $is_map
	 * @return $this
	 * @throws Exception_Key
	 */
	public function set_headers(array $headers, bool $is_map = true): self {
		$this->Headers = [];
		$this->HeadersToIndex = [];
		return $this->add_headers($headers, $is_map);
	}

	/**
	 * @return array
	 */
	public function headers(): array {
		if (method_exists($this, '_cleanHeaders')) {
			$this->_cleanHeaders();
		}
		return $this->Headers;
	}

	/**
	 * Return the file name associated with this CSV
	 *
	 * @return string
	 */
	public function filename(): string {
		return $this->FileName;
	}

	/**
	 * Retrieve the current row's column value
	 *
	 * @param string $name Column to retrieve
	 * @param string $default Value to return if not found
	 * @return string
	 */
	public function column(string $name, string $default = ''): string {
		$name = strtolower($name);
		if (array_key_exists($name, $this->HeadersToIndex)) {
			return $this->Row[$this->HeadersToIndex[$name]];
		}
		return $default;
	}

	/**
	 * Return row index
	 *
	 * @return int
	 */
	public function rowIndex(): int {
		if ($this->RowIndex === 0) {
			return 0;
		}
		return $this->RowIndex - 1;
	}

	/**
	 * Return raw row read from the CSV
	 *
	 * @return array
	 */
	public function row(): array {
		return $this->Row;
	}

	/**
	 * Check to make sure we have a valid file open and ready for operations
	 *
	 * @throws Exception_Semantics
	 */
	protected function _check_file(): void {
		if (!is_resource($this->File)) {
			throw new Exception_Semantics('Must set a file first.');
		}
	}

	/**
	 * Quote a CSV field correctly. If it contains a quote (") a comma (,), or a newline(\n), then quote it.
	 * Quotes are double-quoted, so:
	 *
	 * """Hello"", he said."
	 *
	 * is unquoted as:
	 *
	 * "Hello", he said.
	 *
	 * @param string $x A value to write to a CSV file
	 * @return string A correctly quoted CSV value
	 * @see StringTools::csvQuote
	 */
	public static function quote(string $x): string {
		return StringTools::csvQuote($x);
	}

	/**
	 * Quote a single CSV row
	 *
	 * @param array $x
	 * @return string
	 * @see StringTools::csvQuoteRow
	 */
	public static function quoteRow(array $x): string {
		return StringTools::csvQuoteRow($x);
	}

	/**
	 * Quote multiple CSV rows
	 *
	 * @param array $x of arrays of strings
	 * @return string
	 * @see StringTools::csvQuoteRows
	 */
	public static function quoteRows(array $x): string {
		return StringTools::csvQuoteRows($x);
	}
}
