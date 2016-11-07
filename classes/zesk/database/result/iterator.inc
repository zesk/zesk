<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/zesk/database/result/iterator.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Database_Result_Iterator implements \Iterator {

	/**
	 *
	 * @var Database_Query_Select_Base
	 */
	public $query;

	/**
	 * Current database result
	 *
	 * @var resource
	 */
	private $resource;

	/**
	 * Valid state
	 *
	 * @var boolean
	 */
	protected $_valid;

	/**
	 * Current row
	 *
	 * @var array
	 */
	protected $_row;

	/**
	 * Current key
	 *
	 * @var integer
	 */
	protected $_row_index;

	/**
	 * Query is unbuffered or not
	 *
	 * @var boolean
	 */
	protected $unbuffered = false;

	/**
	 * The value used for the key from each row
	 *
	 * @var mixed
	 */
	protected $_key;

	/**
	 * The value used for the value from each row
	 *
	 * @var mixed
	 */
	protected $_value;

	/**
	 * Debugging on or off
	 *
	 * @var boolean
	 */
	protected $_debug;

	/**
	 * Database
	 *
	 * @var Database
	 */
	protected $db = null;

	/**
	 * Create an row iterator
	 *
	 * @param string $class
	 *        	Class to iterate over
	 * @param Database_Query_Select $query
	 *        	Executed query to iterate
	 */
	function __construct(Database_Query_Select_Base $query, $key = null, $value = null) {
		$this->query = $query;
		$this->resource = null;
		$this->_valid = false;
		$this->_row_index = 0;
		$this->_row = null;
		$this->_value = null;
		$this->_key = null;
		$this->_loaded = false;
		$this->_debug = false;
		if ($key !== null || $value !== null) {
			$this->set_key_value($key, $value);
		}
	}

	/**
	 * Delete it
	 */
	function __destruct() {
		if ($this->resource) {
			$this->db->free($this->resource);
			$this->resource = null;
		}
		unset($this->db);
	}

	/**
	 *
	 * @param string $key
	 *        	Key to use
	 * @param string $value
	 *        	Value to use
	 * @return Database_Result_Iterator
	 */
	public function set_key_value($key = null, $value = null) {
		$this->_key = $key;
		$this->_value = $value;
		return $this;
	}

	/**
	 * Set or get the unbuffered query status
	 *
	 * @param boolean $set
	 * @return Database_Result_Iterator boolean
	 */
	public function unbuffered($set = null) {
		if ($set !== null) {
			$this->unbuffered = $set;
			return $this;
		}
		return $this->unbuffered;
	}

	/**
	 *
	 * @return Database_Query_Select
	 */
	public function query() {
		return $this->query;
	}

	/**
	 * Current query result
	 *
	 * @see Iterator::current()
	 */
	public function current() {
		if ($this->_row === null) {
			return null;
		}
		if ($this->_value) {
			if (!array_key_exists($this->_value, $this->_row)) {
				throw new Exception_Semantics(__("Query result does not contain value \"{value}\"", array(
					"value" => $this->_value
				)));
			}
			return $this->_row[$this->_value];
		}
		return $this->_row;
	}

	/**
	 * Return current row key (ID or index)
	 *
	 * @see Iterator::key()
	 */
	public function key() {
		if ($this->_key) {
			if (!array_key_exists($this->_key, $this->_row)) {
				throw new Exception_Semantics(__("Query result does not contain key {key}", array(
					"key" => $this->_key
				)));
			}
			return $this->_row[$this->_key];
		}
		return $this->_row_index;
	}

	/**
	 * Move to next row
	 *
	 * @see Iterator::next()
	 */
	public function next() {
		$row = $this->db->fetch_assoc($this->resource);
		if (is_array($row)) {
			$this->_row_index = $this->_row_index + 1;
			$this->_valid = true;
			$this->_row = $row;
		} else {
			$this->_valid = false;
			$this->_row = null;
		}
	}

	/**
	 * Return to the beginning of the query
	 *
	 * @see Iterator::rewind()
	 */
	public function rewind() {
		if ($this->resource) {
			$this->db->free($this->resource);
		}
		$this->_loaded = false;
	}

	/**
	 * Do we have more rows to fetch?
	 *
	 * @see Iterator::valid()
	 */
	public function valid() {
		$this->_load();
		return $this->_valid;
	}

	/**
	 * Set/get debug state
	 *
	 * @param boolean|null $set
	 * @return boolean
	 */
	public function debug($set = null) {
		if ($set !== null) {
			$this->_debug = to_bool($set);
			return $this;
		}
		return $this->_debug;
	}

	/**
	 * Convert entire set into an array (uses memory, potentially lots!)
	 *
	 * @return array
	 */
	public function to_array() {
		$result = array();
		foreach ($this as $k => $v) {
			$result[$k] = $v;
		}
		return $result;
	}

	/**
	 * Internal function to init, load a row from the database
	 *
	 * @throws Exception_Semantics
	 */
	private function _load() {
		if ($this->_loaded) {
			return;
		}
		$query = $this->query->__toString();
		if ($this->_debug) {
			echo "Debug: $query\n";
		}
		$this->db = $this->query->database();
		if (!$this->db) {
			throw new Exception_Semantics("No database connection to {name}", array(
				"name" => $this->db->code_name()
			));
		}
		$this->resource = $this->unbuffered ? $this->db->query_unbuffered($query) : $this->db->query($query);
		$this->_row_index = -1;
		$this->next();
		$this->_loaded = true;
	}
}
