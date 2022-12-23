<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Thu Feb 04 12:06:13 EST 2010 12:06:13
 */
namespace zesk\CSV;

use zesk\Options;
use Iterator as BaseIterator;

/**
 * Iterator for CSV files. Options allows attaching extra unstructured data.
 */
class Iterator extends Options implements BaseIterator {
	/**
	 * CSV
	 *
	 * @var Reader
	 */
	private Reader $csv;

	/**
	 * Tell structure used to rewind
	 *
	 * @var array
	 */
	private array $csv_tell;

	/**
	 * Whether rows should be read as readRowAssoc or readRow
	 *
	 * @var boolean
	 * @see Reader::readRowAssoc, Reader::readRow
	 */
	private bool $assoc;

	/**
	 * Return the read map for this reader
	 *
	 * @var string
	 */
	private $use_map = null;

	/**
	 * Current row
	 *
	 * @var array
	 */
	private $row;

	/**
	 * At end of file?
	 *
	 * @var boolean
	 */
	private bool $is_valid;

	public function __construct(Reader $csv, array $options = []) {
		parent::__construct($options);
		$this->csv = $csv;
		$this->csv_tell = $csv->tell();
		$this->assoc = $this->optionBool('assoc', true);
		$this->use_map = $this->option('use_map', null);
		$this->row = null;
		$this->is_valid = true;
	}

	public function rewind(): void {
		$this->assoc = $this->optionBool('assoc', true);
		$this->csv->seek($this->csv_tell);
		$this->is_valid = true;
		$this->next();
	}

	public function current(): array {
		return $this->row;
	}

	public function key(): string {
		return strval($this->csv->rowIndex());
	}

	public function next(): void {
		if ($this->assoc) {
			$this->row = $this->csv->read_row_assoc();
		} else {
			$this->row = $this->csv->read_row();
		}
		if (!is_array($this->row)) {
			$this->is_valid = false;
		} elseif ($this->use_map) {
			$this->row = $this->csv->read_map($this->use_map);
		}
	}

	public function valid(): bool {
		return $this->is_valid;
	}

	/**
	 * Convert iterator to an array
	 *
	 * @param string|null $key_key
	 * @param string|null $value_key
	 * @return array
	 */
	public function toArray(string $key_key = null, string $value_key = null): array {
		$result = [];
		foreach ($this as $index => $row) {
			$value = $value_key === null ? $row : $row[$value_key] ?? null;
			if ($key_key === null) {
				$result[] = $value;
			} else {
				$result[$row[$key_key] ?? $index] = $value;
			}
		}
		return $result;
	}
}
