<?php
/**
 * @package zesk
 * @subpackage tools
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

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
	 * Buffer to load UTF-16 lines
	 *
	 * @var string
	 */
	protected $FileBuffer;

	/**
	 *
	 * @param unknown $filename
	 * @param unknown $options
	 */
	public function __construct($filename = null, array $options = array()) {
		parent::__construct($options);
		$this->FileBuffer = "";
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
	public static function factory($filename, array $options = array()) {
		return new self($filename, $options);
	}

	/**
	 * Retrieve an iterator for this CSV_Reader
	 *
	 * @param string $options
	 * @return CSV_Reader_Iterator
	 */
	public function iterator(array $options = array()) {
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
			'key' => $this->_magic_number(),
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
				"filename" => $this->FileName,
			));
		}
		if ($tell['key'] !== $this->_magic_number()) {
			throw new Exception_Semantics("Invalid tell for CSV File, hashes do not match {filename}", array(
				"filename" => $this->FileName,
			));
		}
		$this->Row = $tell['row'];
		$this->RowIndex = $tell['row_index'];
		$this->FileBuffer = $tell['file_buffer'];
		fseek($this->File, $tell['file_pos']);
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
				return UTF8::to_iso8859($result);
			case "UTF-16":
				if (strpos($this->FileBuffer, $this->LineDelimiter) === false && ($n = strlen($this->FileBuffer)) < 10240) {
					if ($n === 0 && feof($this->File)) {
						return false;
					}
					$read_n = (10240 - $n) * 2;
					$data = fread($this->File, $read_n);
					$data = UTF16::to_iso8859($data, $this->EncodingBigEndian);
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
	public function read_row() {
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
	public function read_row_assoc() {
		$row = $this->read_row();
		if (!is_array($row)) {
			return $row;
		}
		$hh = $this->headers();
		$r = array();
		foreach ($hh as $k => $v) {
			if (is_scalar($v)) {
				$r[$v] = avalue($row, $k);
			} elseif (is_array($v)) {
				foreach ($v as $vv) {
					$r[$vv] = avalue($row, $k);
				}
			}
		}
		$r = $this->postprocess_row($r);
		return $r;
	}

	/**
	 * Override in subclasses
	 *
	 * @param array $row
	 * @return array
	 */
	protected function postprocess_row(array $row) {
		return $row;
	}

	/**
	 * Skip one or more reows in the file.
	 *
	 * NOTE: If called before the headers are read, will not read headers until first read_row/read_row_assoc call.
	 *
	 * @param number $offset
	 */
	public function skip($offset = 1) {
		if ($offset < 0 || !is_integer($offset)) {
			throw new Exception_Parameter("Invalid parameter to CSV_Reader::skip({offset}) of type {type}", array(
				"offset" => $offset,
				"type" => gettype($offset),
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
				"filename" => $this->FileName,
			));
		}
		$tell = ftell($this->File);
		$file_sample = fread($this->File, 1024);
		fseek($this->File, $tell);
		if (StringTools::is_utf8($file_sample, $this->EncodingBigEndian)) {
			$this->Encoding = "UTF-8";
			$this->EncodingSuffix = ".UTF8";
			$file_sample = UTF8::to_iso8859($file_sample);
		} elseif (StringTools::is_utf16($file_sample, $this->EncodingBigEndian)) {
			$this->Encoding = "UTF-16";
			$this->EncodingSuffix = ".UTF16";
			$file_sample = UTF16::to_iso8859($file_sample, $this->EncodingBigEndian);
		} elseif (StringTools::is_ascii($file_sample)) {
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
			"^",
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
}
