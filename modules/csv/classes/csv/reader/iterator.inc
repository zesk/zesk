<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/csv/classes/csv/reader/iterator.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Thu Feb 04 12:06:13 EST 2010 12:06:13
 */

/**
 * Iterator for CSV files. Options allows attaching extra unstructured data.
 */
class CSV_Reader_Iterator extends Options implements Iterator {
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

	public function __construct(CSV_Reader $csv, $options = false) {
		parent::__construct($options);
		$this->csv = $csv;
		$this->csv_tell = $csv->tell();
		$this->assoc = $this->option_bool('assoc', true);
		$this->use_map = $this->option('use_map', null);
		$this->row = null;
		$this->is_valid = true;
	}

	public function rewind() {
		$this->assoc = $this->option_bool('assoc', true);
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

	public function next() {
		if ($this->assoc) {
			$this->row = $this->csv->read_row_assoc();
		} else {
			$this->row = $this->csv->read_row();
		}
		if (!is_array($this->row)) {
			$this->is_valid = false;
		} else if ($this->use_map) {
			$this->row = $this->csv->read_map($this->use_map);
		}
	}

	public function valid() {
		return $this->is_valid;
	}
}
