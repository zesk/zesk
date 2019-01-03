<?php
/**
 * @package zesk
 * @subpackage tools
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

/**
 * CSV
 *
 * Base class for CSV_Reader and CSV_Writer
 *
 * @todo refactor for new naming scheme
 * @package zesk
 * @subpackage system
 */
abstract class CSV extends Options {
	/**
	 * @var resource
	 */
	protected $File;

	/**
	 * @var string
	 */
	protected $FileName;

	/**
	 * @var string
	 */
	protected $Delimiter;

	/**
	 * @var string
	 */
	protected $Enclosure;

	/**
	 * @var string
	 */
	protected $Escape;

	/**
	 * @var string
	 */
	protected $LineDelimiter;

	/**
	 * @var array
	 */
	protected $Headers = null;

	/**
	 * @var array
	 */
	protected $HeadersToIndex = null;

	/**
	 * @var integer
	 */
	protected $RowIndex;

	/**
	 * current row being read or written
	 * @var array
	 */
	protected $Row;

	/**
	 * File encoding
	 * @var string
	 */
	protected $Encoding = 'UTF-8';

	/**
	 *
	 * @var string
	 */
	protected $EncodingSuffix = '.UTF8';

	/**
	 *
	 * @var string
	 */
	protected $EncodingBigEndian = true;

	/**
	 * Create new CSV
	 *
	 * @param mixed $options Array of options
	 */
	public function __construct(array $options = array()) {
		parent::__construct($options);

		$this->RowIndex = 0;
		$this->File = false;
		$this->Delimiter = ",";
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
	 * @see fopen
	 * @return CSV
	 */
	protected function _set_file($filename, $mode, $create = false) {
		if (is_string($filename)) {
			if (!$create && !file_exists($filename)) {
				throw new Exception_File_NotFound($filename);
			}
			$this->File = fopen($filename, $mode);
			if (!$this->File) {
				throw new Exception_File_Permission("Can't open $filename with mode $mode");
			}
		} else {
			throw new Exception_Parameter("CSV::setFile($filename)");
		}

		$this->FileName = $filename;
		$this->Row = false;
		$this->RowIndex = 0;
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
	public function close() {
		if (is_resource($this->File)) {
			fclose($this->File);
			$this->File = null;
			return true;
		}
		return false;
	}

	/**
	 * Add headers to the CSV file
	 *
	 * @param array $headers
	 * @param boolean $is_map The passed in array is a map from internal name => label name
	 * @return CSV
	 */
	public function add_headers(array $headers, $is_map = true) {
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
					$mixed = array(
						$mixed,
					);
				}
				foreach ($mixed as $name) {
					$lowname = strtolower($name);
					if (isset($this->HeadersToIndex[$lowname])) {
						$hmap = $this->HeadersToIndex[$lowname];
						if (is_array($hmap)) {
							$this->HeadersToIndex[$lowname][] = $index;
						} else {
							$this->HeadersToIndex[$lowname] = array(
								$hmap,
								$index,
							);
						}
					} else {
						$this->HeadersToIndex[$lowname] = $index;
					}
				}
			}
		}
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $headers
	 * @return CSV
	 */
	public function set_headers(array $headers, $is_map = true) {
		$this->Headers = array();
		$this->HeadersToIndex = array();
		return $this->add_headers($headers, $is_map);
	}

	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
	public function headers() {
		if (method_exists($this, "_cleanHeaders")) {
			if (!$this->_cleanHeaders()) {
				return false;
			}
		}
		return $this->Headers;
	}

	/**
	 * Return the file name associated with this CSV
	 *
	 * @return string
	 */
	public function filename() {
		return $this->FileName;
	}

	/**
	 * Check if a column has a value (!empty($column))
	 *
	 * @param string $name Header column name
	 *
	 * @return boolean
	 */
	public function is_column_empty($name) {
		$name = strtolower($name);
		if (isset($this->HeadersToIndex[$name])) {
			return empty($this->Row[$this->HeadersToIndex[$name]]);
		}
		return false;
	}

	/**
	 * Retrieve the current row's column value
	 *
	 * @param string $name Column to retrieve
	 * @param mixed $default Value to return if not found
	 * @return string
	 */
	public function column($name, $default = false) {
		$name = strtolower($name);
		if (array_key_exists($name, $this->HeadersToIndex)) {
			return $this->Row[$this->HeadersToIndex[$name]];
		}
		return $default;
	}

	/**
	 * Return row index
	 *
	 * @return unknown
	 */
	public function row_index() {
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
	public function row() {
		return $this->Row;
	}

	/**
	 * Check to make sure we have a valid file open and ready for operations
	 *
	 * @throws Exception_Semantics
	 */
	protected function _check_file() {
		if (!is_resource($this->File)) {
			throw new Exception_Semantics("Must set a file first.");
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
	 * @see StringTools::csv_quote
	 */
	public static function quote($x) {
		return StringTools::csv_quote($x);
	}

	/**
	 * Quote a single CSV row
	 *
	 * @param array $x
	 * @return string
	 * @see StringTools::csv_quote_row
	 */
	public static function quote_row($x) {
		return StringTools::csv_quote_row($x);
	}

	/**
	 * Quote multiple CSV rows
	 *
	 * @param array $x of arrays of strings
	 * @return string
	 * @see StringTools::csv_quote_rows
	 */
	public static function quote_rows($x) {
		return StringTools::csv_quote_rows($x);
	}
}
