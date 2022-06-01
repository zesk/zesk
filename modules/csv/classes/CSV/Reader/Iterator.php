<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Thu Feb 04 12:06:13 EST 2010 12:06:13
 */
namespace zesk;

/**
 * Iterator for CSV files. Options allows attaching extra unstructured data.
 */
class CSV_Reader_Iterator extends Options implements \Iterator {
	/**
	 * CSV
	 *
	 * @var CSV_Reader
	 */
	private $csv;

	/**
	 * Tell structure used to rewind
	 *
	 * @var unknown_type
	 */
	private $csv_tell;

	/**
	 * Whether rows should be read as readRowAssoc or readRow
	 *
	 * @var boolean
	 * @see CSVReader::readRowAssoc, CSVReader::readRow
	 */
	private $assoc;

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
	private $is_valid;

	public function __construct(CSV_Reader $csv, array $options = []) {
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

	public function current() {
		return $this->row;
	}

	public function key() {
		return $this->csv->rowIndex();
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

	public function valid() {
		return $this->is_valid;
	}

	/**
	 * Convert iterator to an array
	 *
	 * @param string|null $key_key
	 * @param string|null $value_key
	 * @return array
	 */
	public function to_array($key_key = null, $value_key = null) {
		$result = [];
		foreach ($this as $index => $row) {
			$value = $value_key === null ? $row : avalue($row, $value_key);
			if ($key_key === null) {
				$result[] = $value;
			} else {
				$result[avalue($row, $key_key, $index)] = $value;
			}
		}
		return $result;
	}
}
