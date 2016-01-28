<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/csv/classes/csv/reader.inc $
 * @package zesk
 * @subpackage tools
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */

/**
 * CSV_Reader
 *
 * Long description
 *
 * @todo refactor to new method naming scheme
 * @package zesk
 * @subpackage tools
 */
class CSV_Reader extends CSV {
	/**
	 * @todo	document this
	 */
	protected $ReadMapGroup;
	/**
	 * @todo	document this
	 */
	protected $ReadMapGroupDefault;
	/**
	 * @todo	document this
	 */
	protected $ReadMapGroupTypes;
	/**
	 * Buffer to load UTF-16 lines
	 *
	 * @var string
	 */
	protected $FileBuffer;

	/**
	 *
	 * @var array
	 */
	protected $translation_map = null;

	function __construct($filename = null, $options = null) {
		parent::__construct($options);
		$this->ReadMapGroup = array();
		$this->ReadMapGroupDefault = array();
		$this->ReadMapGroupTypes = array();
		$this->utf16buf = "";
		if ($filename) {
			$this->filename($filename);
		}
	}

	/**
	 * Create a CSV_Reader
	 *
	 * @param string $filename
	 * @param string $options
	 * @return CSV_Reader
	 */
	public static function factory($filename, $options = null) {
		return zesk::factory(__CLASS__, $filename, $options);
	}

	/**
	 * Retrieve an iterator for this CSV_Reader
	 *
	 * @param string $options
	 * @return CSV_Reader_Iterator
	 */
	public function iterator($options = null) {
		return new CSV_Reader_Iterator($this, $options);
	}

	/**
	 * Get/set filename associated with this CSV_Reader
	 *
	 * WARNING: May affect locale LC_CTYPE depending on file encoding
	 *
	 * @param string $filename
	 * @return CSV_Reader|string
	 */
	public function filename($filename = null) {
		if ($filename !== null) {
			$this->_set_file($filename, 'r');
			$this->determine_encoding();
			return true;
		}
		return $this->FileName;
	}

	/**
	 * Set the file name to read
	 *
	 * @param string $filename CSV file to read
	 * @return CSV_Reader
	 */
	public function set_file($filename) {
		return parent::_set_file($filename, "r")->determine_encoding();
	}

	/**
	 * Retrieve a structure which saves the state of the CSV Reader file read position and row index
	 *
	 * @return mixed
	 */
	public function tell() {
		$offset = ftell($this->File);
		$line_no = $this->RowIndex;
		return array(
			'file_pos' => $offset,
			'file_buffer' => $this->FileBuffer,
			'row_index' => $line_no,
			'row' => $this->Row,
			'key' => $this->_magic_number()
		);
	}

	/**
	 * Seek to a previous tell point. Do not try to construct this structure
	 *
	 * @param array $tell
	 * @throws Exception_Semantics
	 */
	public function seek(array $tell) {
		if (!array_key_exists('key', $tell)) {
			throw new Exception_Semantics("Invalid tell for CSV File {filename}", array(
				"filename" => $this->FileName
			));
		}
		if ($tell['key'] !== $this->_magic_number()) {
			throw new Exception_Semantics("Invalid tell for CSV File, hashes do not match {filename}", array(
				"filename" => $this->FileName
			));
		}
		$this->Row = $tell['row'];
		$this->RowIndex = $tell['row_index'];
		$this->FileBuffer = $tell['file_buffer'];
		fseek($this->File, $tell['file_pos']);
	}

	/**
	 * Set or get a simple mapping for reading objects from a CSV, for example
	 *
	 * Usage:
	 *
	 * 		`$reader->read_map("foo", array("namen" => "name"))` - Sets the read map "foo" to array
	 * 		`$reader->read_map("foo")` - Returns the current row formatted using read map "foo"
	 * 		`$reader->read_map()` - Returns all read map names associated with this reader (["foo","bar"])
	 *
	 * @param string $name Name of this map - case insensitive
	 * @param array $map Array of csv_column => new_key - if null then returns named map
	 * @param string $mapTypes
	 * @param string $defaultMap
	 * @throws Exception_Semantics
	 * @throws Exception_Key
	 * @return boolean
	 */
	function read_map($name = null, array $map = null, array $mapTypes = null, array $defaultMap = null) {
		if (!is_array($this->Headers)) {
			throw new Exception_Semantics("Must have headers before setting map");
		}
		if ($name === null) {
			return array_keys($this->ReadMapGroup);
		}
		if ($map === null) {
			return $this->_get_read_map($name);
		}
		$this->headers();
		$mapGroup = array();
		foreach ($map as $column => $objectMember) {
			$column = strtolower($column);
			if (!isset($this->HeadersToIndex[$column])) {
				throw new Exception_Key("CSV::readSetMap($name,...): $column not found in headers {headers_to_index}", array(
					"headers_to_index" => $this->HeadersToIndex
				));
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
		$this->ReadMapGroup[$name] = $mapGroup;
		if (is_array($defaultMap)) {
			$this->ReadMapGroupDefault[$name] = $defaultMap;
		}
		if (is_array($mapTypes)) {
			$this->ReadMapGroupTypes[$name] = $mapTypes;
		}
		return true;
	}

	/**
	 * Given a source row $data, and a type map
	 * @param array $row
	 * @param array $typeMap
	 * @param array $defaultMap
	 * @throws Exception_Key
	 * @return array
	 */
	private static function apply_types(array $row, array $typeMap, array $defaultMap) {
		foreach ($typeMap as $k => $type) {
			$v = avalue($row, $k);
			if ($v !== null) {
				if (is_string($type)) {
					switch ($type) {
						case "boolean":
							$row[$k] = to_bool($v);
							break;
						case "timestamp":
						case "datetime":
							$row[$k] = Timestamp::factory($v);
							break;
						default:
							throw new Exception_Key("Unknown type map $type in CSV::apply_types");
					}
				} else if (is_array($type)) {
					$v = avalue($type, $v, avalue($defaultMap, $k));
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
	 * @throws Exception_Key
	 * @return array
	 */
	private function _get_read_map($name) {
		$lowname = strtolower($name);
		if (!isset($this->ReadMapGroup[$lowname])) {
			throw new Exception_Key("CSV::readMap($name) doesn't exist");
		}
		$g = $this->ReadMapGroup[$lowname];
		$result = array();
		$r = $this->Row;
		foreach ($g as $index => $cols) {
			$value = $r[$index];
			if (!is_array($cols)) {
				$cols = array(
					$cols
				);
			}
			foreach ($cols as $col) {
				if (isset($result[$col])) {
					$rcol = & $result[$col];
					if (is_array($rcol)) {
						$rcol[] = $value;
					} else {
						$rcol = array(
							$rcol,
							$value
						);
					}
				} else {
					$result[$col] = $value;
				}
			}
		}
		if (isset($this->ReadMapGroupDefault[$lowname])) {
			foreach ($this->ReadMapGroupDefault[$lowname] as $k => $v) {
				if (array_key_exists($k, $result))
					continue;
				$result[$k] = $v;
			}
		}
		$t = avalue($this->ReadMapGroupTypes, $lowname);
		if ($t) {
			$result = self::apply_types($result, $t, avalue($this->ReadMapGroupDefault, $lowname, array()));
		}
		return $result;
	}

	/**
	 * Are we at the end of the file?
	 *
	 * @return boolean
	 */
	private function eof() {
		if ($this->Encoding === "UTF-16") {
			if (!empty($this->FileBuffer)) {
				return false;
			}
		}
		if (!is_resource($this->File)) {
			return true;
		}
		return feof($this->File);
	}

	/**
	 * Read a single line from the file. Converts from UTF-8 and UTF-16 encodings if needed
	 *
	 * @return boolean
	 */
	private function read_line() {
		switch ($this->Encoding) {
			case "UTF-8":
				$result = fgetcsv($this->File, 10240, $this->Delimiter, $this->Enclosure);
				if (!$result) {
					return $result;
				}
				return utf8::to_iso8859($result);
			case "UTF-16":
				if (strpos($this->FileBuffer, $this->LineDelimiter) === false && ($n = strlen($this->FileBuffer)) < 10240) {
					if ($n === 0 && feof($this->File)) {
						return false;
					}
					$read_n = (10240 - $n) * 2;
					$data = fread($this->File, $read_n);
					$data = utf16::to_iso8859($data, $this->EncodingBigEndian);
					$this->FileBuffer .= $data;
				}
				list($line, $this->FileBuffer) = pair($this->FileBuffer, $this->LineDelimiter, $this->FileBuffer, "");
				return str_getcsv($line, $this->Delimiter, $this->Enclosure, $this->Escape);
			default:
				return fgetcsv($this->File, 10240, $this->Delimiter, $this->Enclosure);
		}
	}

	/**
	 * Read the headers from the CSV file
	 *
	 * @return array
	 */
	public function read_headers() {
		$this->_check_file();
		if (!is_array($this->Headers)) {
			$headers = $this->read_line();
			if (!is_array($headers)) {
				return null;
			}
			//$headers = $this->_parseLine($line);
			$this->RowIndex++;
			$this->set_headers($headers, false);
		}
		return $this->headers();
	}

	/**
	 * Read a row from the CSV file, keys are column positions (0 = first, 1 = second, etc.)
	 *
	 * If headers are not defined, reads the first row as headers. If you want to avoid this behavior, call
	 *
	 *     CSV_Reader::set_headers($headers)
	 *
	 * to set the headers prior to reading the first row
	 *
	 * @see CSV_Reader::read_row_assoc
	 *
	 * @return array|false
	 */
	function read_row() {
		$this->_check_file();
		if (!is_array($this->Headers)) {
			$this->read_headers();
		}
		$f = $this->File;
		if ($this->eof()) {
			$this->Row = null;
			return false;
		}
		$this->Row = $this->read_line();
		$this->RowIndex = $this->RowIndex + 1;
		return $this->Row;
	}

	/**
	 * Read a row and map column names using our headers
	 *
	 * @return array
	 */
	function read_row_assoc() {
		$row = $this->read_row();
		if (!is_array($row)) {
			return $row;
		}
		$hh = $this->headers();
		$r = array();
		foreach ($hh as $k => $v) {
			if (is_scalar($v)) {
				$r[$v] = avalue($row, $k);
			} else if (is_array($v)) {
				foreach ($v as $vv) {
					$r[$vv] = avalue($row, $k);
				}
			}
		}
		if (is_array($this->translation_map)) {
			$r = $this->_postprocess_row($r);
		}
		if (avalue(func_get_args(), 0) === true) {
			log::warning("{class}::{method} passing boolean lower as first parameter is deprecated 2015-04-16", array(
				"class" => __CLASS__,
				"method" => __METHOD__
			));
			zesk::deprecated();
			return array_change_key_case($r);
		}
		return $r;
	}

	/**
	 * Set columns to be converted to boolean upon reading
	 *
	 * @param mixed $columns
	 * @return CSV_Reader
	 */
	function add_translation_map_boolean($columns) {
		return $this->_add_translation_map_type(to_list($columns), Class_Object::type_boolean);
	}

	/**
	 * Skip one or more reows in the file.
	 *
	 * NOTE: If called before the headers are read, will not read headers until first read_row/read_row_assoc call.
	 *
	 * @param number $offset
	 */
	function skip($offset = 1) {
		if ($offset < 0 || !is_integer($offset)) {
			throw new Exception_Parameter("Invalid parameter to CSV_Reader::skip({offset}) of type {type}", array(
				"offset" => $offset,
				"type" => gettype($offset)
			));
			return;
		}
		while ($offset-- > 0) {
			$this->read_line();
			$this->RowIndex++;
		}
	}

	/**
	 * Determine the encoding of the file by peeking at the first 1K bytes
	 *
	 * @throws Exception_File_Format
	 * @return void
	 */
	private function determine_encoding() {
		if (!is_resource($this->File)) {
			throw new Exception_Semantics("File is not a resource: {filename}", array(
				"filename" => $this->FileName
			));
		}
		$tell = ftell($this->File);
		$file_sample = fread($this->File, 1024);
		fseek($this->File, $tell);
		if (str::is_utf8($file_sample, $this->EncodingBigEndian)) {
			$this->Encoding = "UTF-8";
			$this->EncodingSuffix = ".UTF8";
			$file_sample = utf8::to_iso8859($file_sample);
		} else if (str::is_utf16($file_sample, $this->EncodingBigEndian)) {
			$this->Encoding = "UTF-16";
			$this->EncodingSuffix = ".UTF16";
			$file_sample = utf16::to_iso8859($file_sample, $this->EncodingBigEndian);
		} else if (str::is_ascii($file_sample)) {
			$this->Encoding = "ISO-8859-1";
			$this->EncodingSuffix = ".ISO8859";
		} else {
			throw new Exception_File_Format("Unknown file encoding");
		}
		$old_locale = setlocale(LC_CTYPE, "en" . $this->EncodingSuffix);
		if ($old_locale === false) {
			$this->EncodingSuffix = null;
		} else {
			setlocale(LC_CTYPE, $old_locale);
		}
		$this->_determine_delimiter($file_sample);
	}

	/**
	 * Retrieve a magic number to avoid bogus data in tell/seek
	 *
	 * @return string
	 */
	private function _magic_number() {
		return md5($this->FileName . filesize($this->FileName));
	}

	/**
	 * Determine the delimiter used between CSV cells per line by sampling and counting each in the selected line
	 *
	 * @param string $line
	 */
	private function _determine_delimiter($line) {
		$fieldChars = array(
			",",
			"\t",
			"^"
		);
		$this->Delimiter = $fieldChars[0];
		$maxCount = -1;
		foreach ($fieldChars as $fieldChar) {
			$n = substr_count($line, $fieldChar);
			if ($n > $maxCount) {
				$maxCount = $n;
				$this->Delimiter = $fieldChar;
			}
		}
	}

	/**
	 * Add translation map for a specific type
	 *
	 * @param array $columns
	 * @param string $type Class_Object::type_foo type
	 * @return CSV_Reader
	 */
	private function _add_translation_map_type(array $columns, $type) {
		foreach ($columns as $column) {
			if (!in_array($column, $this->Headers)) {
				log::warning("Unknown header key {key} in CSV reader for file {file} (type {type})", array(
					"key" => $column,
					"type" => $type,
					"file" => $this->FileName
				));
				continue;
			}
			$this->translation_map[$column] = $type;
		}
		return $this;
	}

	/**
	 *
	 * @param array $row
	 */
	private function _postprocess_row(array $row) {
		foreach ($this->translation_map as $column => $type) {
			if (!array_key_exists($column, $row)) {
				continue;
			}
			$method = "_convert_to_$type";
			$row[$column] = $this->$method($row[$column], $row, $column);
		}
		return $row;
	}

	/**
	 * Convert data to boolean
	 */
	private function _convert_to_boolean($value, array $row, $column) {
		return to_bool($value);
	}

	/**
	 * @deprecated 2015-04-16
	 * @param number $offset
	 */
	function skipRow($offset = 1) {
		zesk::deprecated();
		return $this->skip($offset);
	}

	/**
	 * Retrieve the read map of name
	 *
	 * @param string $name
	 * @deprecated 2015-04-16
	 * @throws Exception_Key
	 * @return array
	 */
	function getReadMap($name) {
		zesk::deprecated();
		return $this->read_map($name);
	}

	/**
	 * Read the headers from the CSV file
	 *
	 * @return array
	 * @deprecated 2015-04-16
	 */
	public function readHeaders() {
		zesk::deprecated();
		return $this->read_headers();
	}

	/**
	 * @deprecated 2015-04-16
	 * @see CSV_Reader::read_row_assoc
	 */
	function readRowAssoc($lower = false) {
		zesk::deprecated();
		return $this->read_row_assoc($lower);
	}
	/**
	 * @deprecated 2015-04-16
	 * @see CSV_Reader::read_row
	 */
	function readRow() {
		zesk::deprecated();
		return $this->read_row();
	}

	/**
	 *
	 * @param string $filename
	 * @deprecated 2015-04-16
	 * @return CSV_Reader
	 */
	public function setFile($filename) {
		zesk::deprecated();
		return $this->filename($filename);
	}

	/**
	 *
	 * @deprecated 2015-04-16
	 * @todo No replacement - needed?
	 * @return multitype:
	 */
	function getReadMapNames() {
		zesk::deprecated();
		return array_keys($this->ReadMapGroup);
	}

	/**
	 * @deprecated 2015-04-16
	 * @see CSV_Reader::set_read_map
	 */
	function setReadMap($name, $map, $mapTypes = false, $defaultMap = false) {
		zesk::deprecated();
		return $this->set_read_map($name, $map, $mapTypes, $defaultMap);
	}
}

