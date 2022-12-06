<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
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
	public Database_Query_Select_Base $query;

	/**
	 * Current database result
	 *
	 * @var resource
	 */
	private mixed $resource;

	/**
	 * Valid state
	 *
	 * @var boolean
	 */
	protected bool $_valid;

	/**
	 * Current row
	 *
	 * @var array
	 */
	protected array $_row = [];

	/**
	 * Current key
	 *
	 * @var integer
	 */
	protected int $_row_index = 0;

	/**
	 * Query is unbuffered or not
	 *
	 * @var boolean
	 */
	protected bool $unbuffered = false;

	/**
	 * The value used for the key from each row
	 *
	 * @var mixed
	 */
	protected string $_key;

	/**
	 * The value used for the value from each row
	 *
	 * @var mixed
	 */
	protected mixed $_value;

	/**
	 * Debugging on or off
	 *
	 * @var boolean
	 */
	protected bool $_debug;

	/**
	 * Database
	 *
	 * @var Database
	 */
	protected Database $db;

	/**
	 * Create an row iterator
	 *
	 * @param string $class
	 *            Class to iterate over
	 * @param Database_Query_Select $query
	 *            Executed query to iterate
	 */
	public function __construct(Database_Query_Select_Base $query, string $key = '', string $value = '') {
		$this->query = $query;
		$this->db = $query->database();
		$this->resource = null;
		$this->_valid = false;
		$this->_row_index = 0;
		$this->_row = [];
		$this->_key = $key;
		$this->_value = $value;
		$this->_loaded = false;
		$this->_debug = false;
		$this->set_extract($key, $value);
	}

	/**
	 * Delete it
	 */
	public function __destruct() {
		if ($this->resource) {
			$this->db->free($this->resource);
			$this->resource = null;
		}
		unset($this->db);
	}

	/**
	 *
	 * @param string $key
	 *            Key to use
	 * @param string $value
	 *            Value to use
	 * @return Database_Result_Iterator
	 */
	public function set_extract(string $key = '', string $value = ''): self {
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
	public function setUnbuffered(bool $set) {
		$this->unbuffered = $set;
		return $this;
	}

	/**
	 * Set or get the unbuffered query status
	 *
	 * @return bool
	 */
	public function unbuffered(): bool {
		return $this->unbuffered;
	}

	/**
	 *
	 * @return Database_Query_Select
	 */
	public function query(): Database_Query_Select {
		return $this->query;
	}

	/**
	 * Current query result
	 *
	 * @see Iterator::current()
	 */
	public function current(): mixed {
		if (!$this->valid) {
			return null;
		}
		if ($this->_value) {
			if (!array_key_exists($this->_value, $this->_row)) {
				throw new Exception_Semantics(__('Query result does not contain value "{value}"', ['value' => $this->_value, ]));
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
	public function key(): mixed {
		if ($this->_key) {
			if (!array_key_exists($this->_key, $this->_row)) {
				throw new Exception_Semantics(__('Query result does not contain key {key}', ['key' => $this->_key, ]));
			}
			return $this->_row[$this->_key];
		}
		return $this->_row_index;
	}

	/**
	 * Load the next row, called from subclasses during next
	 *
	 * @return void
	 * @deprecated 2022-01
	 */
	protected function dbnext(): void {
		$this->db->application->deprecated('dbnext()');
		$this->database_next();
	}

	/**
	 * Load the next row, called from subclasses during next
	 *
	 * @return void
	 */
	protected function database_next(): void {
		$row = $this->db->fetchAssoc($this->resource);
		if (is_array($row)) {
			$this->_row_index = $this->_row_index + 1;
			$this->_valid = true;
			$this->_row = $row;
		} else {
			$this->_valid = false;
			$this->_row = [];
		}
	}

	/**
	 * Move to next row
	 *
	 * @see Iterator::next()
	 */
	public function next(): void {
		$this->database_next();
	}

	/**
	 * Return to the beginning of the query
	 *
	 * @see Iterator::rewind()
	 */
	public function rewind(): void {
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
	public function valid(): bool {
		$this->_load();
		return $this->_valid;
	}

	/**
	 * Set/get debug state
	 *
	 * @return bool
	 */
	public function debug(): bool {
		return $this->_debug;
	}

	/**
	 * Set/get debug state
	 *
	 * @param boolean|null $set
	 * @return boolean
	 */
	public function setDebug(bool $set): self {
		$this->_debug = $set;
		return $this;
	}

	/**
	 * Convert entire set into an array (uses memory, potentially lots!)
	 *
	 * @return array
	 */
	public function toArray(): array {
		$result = [];
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
	private function _load(): void {
		if ($this->_loaded) {
			return;
		}
		$query = $this->query->__toString();
		if ($this->_debug) {
			$this->db->application->logger->debug('{class}: {query}', ['class' => get_class($this), 'query' => $query]);
		}
		$this->resource = $this->unbuffered ? $this->db->query_unbuffered($query) : $this->db->query($query);
		$this->_row_index = -1;
		$this->next();
		$this->_loaded = true;
	}
}
