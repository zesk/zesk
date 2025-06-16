<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage tools
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\CSV;

use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ProtocolException;
use zesk\Options;
use zesk\StringTools;

/**
 * CSV
 *
 * Base class for CSV_Reader and CSV_Writer
 *
 * @todo       refactor for new naming scheme
 * @package    zesk
 * @subpackage system
 */
abstract class Base extends Options
{
	/**
	 * @var resource
	 */
	protected mixed $File;

	/**
	 * @var string
	 */
	protected string $file;

	/**
	 * @var string
	 */
	protected string $delimiter;

	/**
	 * @var string
	 */
	protected string $enclosure;

	/**
	 * @var string
	 */
	protected string $escape;

	/**
	 * @var string
	 */
	protected string $lineDelimiter;

	/**
	 * @var array
	 */
	protected array $headers = [];

	/**
	 * @var array
	 */
	protected array $headersToIndex = [];

	/**
	 * @var integer
	 */
	protected int $rowIndex;

	/**
	 * The current row being read or written
	 *
	 * @var array
	 */
	protected array $row;

	/**
	 * File encoding
	 *
	 * @var string
	 */
	protected string $encoding = 'UTF-8';

	/**
	 *
	 * @var string
	 */
	protected string $encodingSuffix = '.UTF8';

	/**
	 *
	 * @var bool
	 */
	protected bool $encodingBigEndian = true;

	/**
	 * Create new CSV
	 *
	 * @param mixed $options Array of options
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);

		$this->rowIndex = 0;
		$this->File = false;
		$this->delimiter = ',';
		$this->enclosure = '"';
		$this->escape = '\\';
		$this->lineDelimiter = "\n";
	}

	/**
	 * Set the file associated with this CSV object
	 *
	 * @param string  $filename File the path to use
	 * @param string  $mode     File mode (fopen)
	 * @param boolean $create   Create the file if it doesn't exist
	 * @return self
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @see fopen
	 */
	protected function _setFile(string $filename, string $mode, bool $create = false): self
	{
		if (!$create && !file_exists($filename)) {
			throw new FileNotFound($filename);
		}
		$this->File = fopen($filename, $mode);
		if (!$this->File) {
			throw new FilePermission("Can't open $filename with mode $mode");
		}

		$this->file = $filename;
		$this->row = [];
		$this->rowIndex = 0;
		return $this;
	}

	/**
	 * Close the CSV file
	 *
	 * @return void
	 */
	public function close(): void
	{
		if (is_resource($this->File)) {
			fclose($this->File);
			$this->File = null;
		}
	}

	/**
	 * Add headers to the CSV file
	 *
	 * @param array   $headers
	 * @param boolean $is_map The passed in array is a map from internal name => label name
	 * @return Base
	 * @throws KeyNotFound
	 */
	public function addHeaders(array $headers, bool $is_map = true): self
	{
		if ($is_map) {
			foreach ($headers as $column => $header_name) {
				$index = count($this->headers);
				$this->headers[$index] = $header_name;
				$this->headersToIndex[strtolower($column)] = $index;
			}
		} else {
			foreach ($headers as $mixed) {
				$index = count($this->headers);
				$this->headers[$index] = $mixed;
				if (!is_array($mixed)) {
					$mixed = [
						$mixed,
					];
				}
				foreach ($mixed as $name) {
					if (!is_string($name)) {
						throw new KeyNotFound('Invalid key {name}');
					}
					$lowName = strtolower($name);
					if (array_key_exists($lowName, $this->headersToIndex)) {
						$this->headersToIndex[$lowName][] = $index;
					} else {
						$this->headersToIndex[$lowName] = [$index];
					}
				}
			}
		}
		return $this;
	}

	/**
	 * @param array $headers
	 * @param bool  $is_map
	 * @return $this
	 * @throws KeyNotFound
	 */
	public function setHeaders(array $headers, bool $is_map = true): self
	{
		$this->headers = [];
		$this->headersToIndex = [];
		return $this->addHeaders($headers, $is_map);
	}

	/**
	 * @return array
	 */
	public function headers(): array
	{
		if (method_exists($this, '_cleanHeaders')) {
			$this->_cleanHeaders();
		}
		return $this->headers;
	}

	/**
	 * Return the file name associated with this CSV
	 *
	 * @return string
	 */
	public function filename(): string
	{
		return $this->file;
	}

	/**
	 * Retrieve the current row's column value
	 *
	 * @param string $name    Column to retrieve
	 * @param string $default Value to return if not found
	 * @return string
	 */
	public function column(string $name, string $default = ''): string
	{
		$name = strtolower($name);
		if (array_key_exists($name, $this->headersToIndex)) {
			return $this->row[$this->headersToIndex[$name]];
		}
		return $default;
	}

	/**
	 * Return row index
	 *
	 * @return int
	 */
	public function rowIndex(): int
	{
		if ($this->rowIndex === 0) {
			return 0;
		}
		return $this->rowIndex - 1;
	}

	/**
	 * Return raw row read from the CSV
	 *
	 * @return array
	 */
	public function row(): array
	{
		return $this->row;
	}

	/**
	 * Check to make sure we have a valid file open and ready for operations
	 *
	 * @throws ProtocolException
	 */
	protected function _check_file(): void
	{
		if (!is_resource($this->File)) {
			throw new ProtocolException('Must set a file first.');
		}
	}

	/**
	 * Quote a CSV field correctly. If it contains a quote (`"`) a comma (`,`), or a newline(`\n`), then quote it.
	 *
	 * Quotes are double-quoted, so:
	 *
	 * """Hello,"" he said."
	 *
	 * is unquoted as:
	 *
	 * "Hello," he said.
	 *
	 * @param string $x A value to write to a CSV file
	 * @return string A correctly quoted CSV value
	 * @see StringTools::csvQuote
	 */
	public static function quote(string $x): string
	{
		return StringTools::csvQuote($x);
	}

	/**
	 * Quote a single CSV row
	 *
	 * @param array $x
	 * @return string
	 * @see StringTools::csvQuoteRow
	 */
	public static function quoteRow(array $x): string
	{
		return StringTools::csvQuoteRow($x);
	}

	/**
	 * Quote multiple CSV rows
	 *
	 * @param array $x of arrays of strings
	 * @return string
	 * @see StringTools::csvQuoteRows
	 */
	public static function quoteRows(array $x): string
	{
		return StringTools::csvQuoteRows($x);
	}
}
