<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage tools
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\CSV;

use zesk\Exception_File_Format;
use zesk\Exception_File_NotFound;
use zesk\Exception_File_Permission;
use zesk\Exception_Key;
use zesk\Exception_Parameter;
use zesk\Exception_Semantics;
use zesk\StopIteration;
use zesk\StringTools;
use zesk\UTF16;
use zesk\UTF8;

/**
 * CSV_Reader
 *
 * Long description
 *
 * @package zesk
 * @subpackage tools
 */
class Reader extends Base {
	/**
	 * Buffer to load UTF-16 lines
	 *
	 * @var string
	 */
	protected string $FileBuffer;

	/**
	 * @param string $filename
	 * @param array $options
	 * @throws Exception_File_Format
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 */
	public function __construct(string $filename = '', array $options = []) {
		parent::__construct($options);
		$this->FileBuffer = '';
		if ($filename) {
			$this->setFilename($filename);
		}
	}

	/**
	 * Create a Reader
	 *
	 * @param string $filename
	 * @param array $options
	 * @return self
	 * @throws Exception_File_Format
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 */
	public static function factory(string $filename = '', array $options = []): self {
		return new self($filename, $options);
	}

	/**
	 * Retrieve an iterator for this CSV_Reader
	 *
	 * @param array $options
	 * @return Iterator
	 */
	public function iterator(array $options = []): Iterator {
		return new Iterator($this, $options);
	}

	/**
	 * Get filename associated with this Reader
	 *
	 * @return string
	 */
	public function filename(): string {
		return $this->FileName;
	}

	/**
	 * @param string $filename
	 * @return self
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Format
	 * @throws Exception_File_Permission
	 */
	public function setFilename(string $filename): self {
		parent::_setFile($filename, 'r');
		$this->determineEncoding();
		return $this;
	}

	/**
	 * Retrieve a structure which saves the state of the CSV Reader file read position and row index
	 *
	 * @return mixed
	 */
	public function tell(): array {
		$offset = ftell($this->File);
		$line_no = $this->RowIndex;
		return [
			'file_pos' => $offset, 'file_buffer' => $this->FileBuffer, 'row_index' => $line_no, 'row' => $this->Row,
			'key' => $this->_magicNumber(),
		];
	}

	/**
	 * Seek to a previous tell point. Do not try to construct this structure
	 *
	 * @param array $tell
	 * @throws Exception_Semantics
	 */
	public function seek(array $tell): void {
		if (!array_key_exists('key', $tell)) {
			throw new Exception_Semantics('Invalid tell for CSV File {filename}', [
				'filename' => $this->FileName,
			]);
		}
		if ($tell['key'] !== $this->_magicNumber()) {
			throw new Exception_Semantics('Invalid tell for CSV File, hashes do not match {filename}', [
				'filename' => $this->FileName,
			]);
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
	private function eof(): bool {
		if ($this->Encoding === 'UTF-16') {
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
	 * @return array
	 * @throws Exception_File_Format
	 */
	private function read_line(): array {
		switch ($this->Encoding) {
			case 'UTF-8':
				$result = fgetcsv($this->File, 10240, $this->Delimiter, $this->Enclosure);
				if (!is_array($result)) {
					throw new Exception_File_Format('fgetcsv failed');
				}
				foreach ($result as $index => $value) {
					$result[$index] = UTF8::to_iso8859($value);
				}
				return $result;
			case 'UTF-16':
				if (!str_contains($this->FileBuffer, $this->LineDelimiter) && ($n = strlen($this->FileBuffer)) < 10240) {
					if ($n === 0 && feof($this->File)) {
						return false;
					}
					$read_n = (10240 - $n) * 2;
					$data = fread($this->File, $read_n);
					$data = UTF16::to_iso8859($data, $this->EncodingBigEndian);
					$this->FileBuffer .= $data;
				}
				[$line, $this->FileBuffer] = pair($this->FileBuffer, $this->LineDelimiter, $this->FileBuffer, '');
				$result = str_getcsv($line, $this->Delimiter, $this->Enclosure, $this->Escape);
				return $result;
			default:
				$result = fgetcsv($this->File, 10240, $this->Delimiter, $this->Enclosure);
				if (!is_array($result)) {
					throw new Exception_File_Format('fgetcsv failed');
				}
				return $result;
		}
	}

	/**
	 * Read the headers from the CSV file
	 *
	 * @return array
	 * @throws Exception_Semantics|Exception_Key|Exception_File_Format
	 */
	public function read_headers(): array {
		$this->_check_file();
		if (!count($this->Headers)) {
			$headers = $this->read_line();
			if (!count($headers)) {
				throw new Exception_Semantics('No headers');
			}
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
	 * @return array
	 * @throws Exception_File_Format
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function read_row(): array {
		$this->_check_file();
		if (!count($this->Headers)) {
			$this->read_headers();
		}
		if ($this->eof()) {
			$this->Row = [];

			throw new StopIteration();
		}
		$this->Row = $this->read_line();
		$this->RowIndex = $this->RowIndex + 1;
		return $this->Row;
	}

	/**
	 *
	 * Read a row and map column names using our headers
	 *
	 * @return array
	 * @throws Exception_File_Format
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function read_row_assoc() {
		$row = $this->read_row();
		$hh = $this->headers();
		$r = [];
		foreach ($hh as $k => $v) {
			if (is_scalar($v)) {
				$r[$v] = $row[$k] ?? null;
			} elseif (is_array($v)) {
				foreach ($v as $vv) {
					$r[$vv] = $row[$k] ?? null;
				}
			}
		}
		$r = $this->postProcessRow($r);
		return $r;
	}

	/**
	 * Override in subclasses
	 *
	 * @param array $row
	 * @return array
	 */
	protected function postProcessRow(array $row) {
		return $row;
	}

	/**
	 * Skip one or more reows in the file.
	 *
	 * NOTE: If called before the headers are read, will not read headers until first read_row/read_row_assoc call.
	 *
	 * @param int $offset
	 * @return void
	 * @throws Exception_File_Format
	 * @throws Exception_Parameter
	 */
	public function skip(int $offset = 1): void {
		if ($offset < 0 || !is_int($offset)) {
			throw new Exception_Parameter('Invalid parameter to CSV_Reader::skip({offset}) of type {type}', [
				'offset' => $offset, 'type' => gettype($offset),
			]);
		}
		while ($offset-- > 0) {
			$this->read_line();
			$this->RowIndex++;
		}
	}

	/**
	 * Determine the encoding of the file by peeking at the first 1K bytes
	 *
	 * @return void
	 * @throws Exception_File_Format
	 * @throws Exception_Semantics
	 */
	private function determineEncoding(): void {
		if (!is_resource($this->File)) {
			throw new Exception_Semantics('File is not a resource: {filename}', [
				'filename' => $this->FileName,
			]);
		}
		$tell = ftell($this->File);
		$file_sample = fread($this->File, 1024);
		fseek($this->File, $tell);
		if (StringTools::isUTF8($file_sample, $this->EncodingBigEndian)) {
			$this->Encoding = 'UTF-8';
			$this->EncodingSuffix = '.UTF8';
			$file_sample = UTF8::to_iso8859($file_sample);
		} elseif (StringTools::isUTF16($file_sample, $this->EncodingBigEndian)) {
			$this->Encoding = 'UTF-16';
			$this->EncodingSuffix = '.UTF16';
			$file_sample = UTF16::to_iso8859($file_sample, $this->EncodingBigEndian);
		} elseif (StringTools::isASCII($file_sample)) {
			$this->Encoding = 'ISO-8859-1';
			$this->EncodingSuffix = '.ISO8859';
		} else {
			throw new Exception_File_Format('Unknown file encoding');
		}
		$old_locale = setlocale(LC_CTYPE, 'en' . $this->EncodingSuffix);
		if ($old_locale === false) {
			$this->EncodingSuffix = '';
		} else {
			setlocale(LC_CTYPE, $old_locale);
		}
		$this->Delimiter = $this->_determineDelimiter($file_sample);
	}

	/**
	 * Retrieve a magic number to avoid bogus data in tell/seek
	 *
	 * @return string
	 */
	private function _magicNumber(): string {
		return md5($this->FileName . '-' . filesize($this->FileName));
	}

	/**
	 * Determine the delimiter used between CSV cells per line by sampling and counting each in the selected line
	 *
	 * @param string $line
	 * @return string
	 */
	private function _determineDelimiter(string $line): string {
		$fieldChars = [
			',', "\t", '^',
		];
		$delimiter = $fieldChars[0];
		$maxCount = -1;
		foreach ($fieldChars as $fieldChar) {
			$n = substr_count($line, $fieldChar);
			if ($n > $maxCount) {
				$maxCount = $n;
				$delimiter = $fieldChar;
			}
		}
		return $delimiter;
	}
}
