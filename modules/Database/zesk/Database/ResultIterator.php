<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Database;

use Iterator;
use Throwable;
use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\SQLException;
use zesk\Database\Exception\TableNotFound;
use zesk\Exception\Semantics;

/**
 *
 * @author kent
 *
 */
class ResultIterator implements Iterator
{
	/**
	 *
	 * @var SelectableInterface
	 */
	public SelectableInterface $query;

	/**
	 * Current database result
	 *
	 * @var resource
	 */
	private mixed $resource;

	/**
	 * Loaded state
	 *
	 * @var boolean
	 */
	protected bool $_loaded;

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
	 * @var Base
	 */
	protected Base $db;

	/**
	 * Create a row iterator
	 *
	 * @param SelectableInterface $query
	 * @param string $key
	 * @param string $value
	 */
	public function __construct(SelectableInterface $query, string $key = '', string $value = '')
	{
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
		$this->setExtract($key, $value);
	}

	/**
	 * Delete it
	 */
	public function __destruct()
	{
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
	 * @return self
	 */
	public function setExtract(string $key = '', string $value = ''): self
	{
		$this->_key = $key;
		$this->_value = $value;
		return $this;
	}

	/**
	 * Set or get the unbuffered query status
	 *
	 * @param boolean $set
	 * @return self
	 */
	public function setUnbuffered(bool $set): self
	{
		$this->unbuffered = $set;
		return $this;
	}

	/**
	 * Set or get the unbuffered query status
	 *
	 * @return bool
	 */
	public function unbuffered(): bool
	{
		return $this->unbuffered;
	}

	/**
	 *
	 * @return SelectableInterface
	 */
	public function query(): SelectableInterface
	{
		return $this->query;
	}

	/**
	 * Current query result
	 *
	 * @return mixed
	 * @throws Semantics
	 */
	public function current(): mixed
	{
		if (!$this->_valid) {
			return null;
		}
		if ($this->_value) {
			if (!array_key_exists($this->_value, $this->_row)) {
				throw new Semantics('Query result does not contain value "{value}"', ['value' => $this->_value, ]);
			}
			return $this->_row[$this->_value];
		}
		return $this->_row;
	}

	/**
	 * Return current row key (ID or index)
	 *
	 * @return mixed
	 * @throws Semantics
	 */
	public function key(): mixed
	{
		if ($this->_key) {
			if (!array_key_exists($this->_key, $this->_row)) {
				throw new Semantics('Query result does not contain key {key}', ['key' => $this->_key, ]);
			}
			return $this->_row[$this->_key];
		}
		return $this->_row_index;
	}

	/**
	 * Load the next row, called from subclasses during next
	 *
	 * @return void
	 */
	protected function databaseNext(): void
	{
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
	public function next(): void
	{
		$this->databaseNext();
	}

	/**
	 * Return to the beginning of the query
	 *
	 * @see Iterator::rewind()
	 */
	public function rewind(): void
	{
		if ($this->resource) {
			$this->db->free($this->resource);
		}
		$this->_loaded = false;
	}

	/**
	 * Do we have more rows to fetch?
	 *
	 * @return bool
	 */
	public function valid(): bool
	{
		try {
			$this->_load();
		} catch (Throwable) {
		}
		return $this->_valid;
	}

	/**
	 * Get debug state
	 *
	 * @return bool
	 */
	public function debug(): bool
	{
		return $this->_debug;
	}

	/**
	 * Set debug state
	 *
	 * @param boolean $set
	 * @return self
	 * @todo test this
	 */
	public function setDebug(bool $set): self
	{
		$this->_debug = $set;
		return $this;
	}

	/**
	 * Convert entire set into an array (uses memory, potentially lots!)
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$result = [];
		foreach ($this as $k => $v) {
			$result[$k] = $v;
		}
		return $result;
	}

	/**
	 * Internal function to init, load a row from the database
	 *
	 * @return void
	 * @throws Duplicate
	 * @throws SQLException
	 * @throws TableNotFound
	 */
	private function _load(): void
	{
		if ($this->_loaded) {
			return;
		}
		$query = $this->query->__toString();
		if ($this->_debug) {
			$this->db->application->logger->debug('{class}: {query}', ['class' => get_class($this), 'query' => $query]);
		}
		$this->resource = $this->db->query($query, ['unbuffered' => $this->unbuffered]);
		$this->_row_index = -1;
		$this->next();
		$this->_loaded = true;
	}
}
