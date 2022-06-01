<?php
declare(strict_types=1);
/**
 * Delete
 *
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Query_Insert extends Database_Query_Edit {
	/**
	 * This is a REPLACE command
	 *
	 * @var boolean
	 */
	protected bool $replace = false;

	/**
	 *
	 * @var ?Database_Query_Select
	 */
	protected ?Database_Query_Select $select = null;

	/**
	 * INSERT INTO {$this->into}
	 *
	 * @var string
	 */
	protected string $into;

	/**
	 * Result
	 *
	 * @var mixed
	 */
	protected mixed $result;

	/**
	 * Construct a new insert query
	 *
	 * @param Database $db
	 */
	public function __construct(Database $db) {
		parent::__construct('INSERT', $db);
	}

	/**
	 * Getter for "into" which table
	 *
	 * @param ?string $set Deprecated method to set into table name
	 * @return $this|string
	 * @throws Exception_Deprecated
	 */
	public function into(string $set = null): self|string {
		if ($set !== null) {
			zesk()->deprecated('setter/getter changed to PSR');
			return $this->setInto($set);
		}
		return $this->into;
	}

	/**
	 * Setter for "into" which table
	 *
	 * @param string $set Into table name
	 * @return self
	 */
	public function setInto(string $set): self {
		$this->setTable($set);
		$this->into = $set;
		return $this;
	}

	/**
	 * Get replace mode
	 *
	 * @return bool
	 */
	public function replace(): bool {
		return $this->replace;
	}

	/**
	 * @param bool $set
	 * @return $this
	 */
	public function setReplace(bool $set): self {
		$this->replace = $set;
		return $this;
	}

	/**
	 * Set to insert mode
	 *
	 * @return Database_Query_Insert
	 */
	public function insert(): self {
		$this->replace = false;
		return $this;
	}

	/**
	 * Insert from a SELECT query
	 *
	 * @param Database_Query_Select $query
	 * @return Database_Query_Insert
	 */
	public function select(Database_Query_Select $query): self {
		$this->select = $query;
		return $this;
	}

	/**
	 * Convert this query to SQL
	 *
	 * @return string
	 * @throws Exception_Parameter
	 * @throws Exception_Unimplemented
	 */
	public function __toString() {
		$options = [
			'table' => $this->into,
			'low_priority' => $this->low_priority,
		];
		if ($this->replace) {
			$options['verb'] = 'REPLACE';
		}
		if ($this->select) {
			return $this->sql()->insert_select($this->into, $this->select->what(), strval($this->select), $options);
		}
		return $this->sql()->insert($this->into, $this->values(), $options);
	}

	/**
	 * @return int|null
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Unimplemented
	 */
	private function _execute(): ?int {
		if ($this->select) {
			$sql = $this->__toString();
			return $this->database()->query($sql);
		}
		$options = [
			'table' => $this->into,
			'values' => $this->values,
		];
		if ($this->replace) {
			$options['verb'] = 'REPLACE';
		}
		$this->result = $this->db->insert($this->into, $this->values, $options);
		return $this->result;
	}

	/**
	 * Execute the insert and retrieve the ID created
	 *
	 * @return int
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 */
	public function id(): int {
		if ($this->low_priority) {
			throw new Exception_Semantics('Can not execute query as low priority and retrieve id: ' . $this->__toString());
		}
		if ($this->select) {
			throw new Exception_Semantics('Can not execute query as select and retrieve id: ' . $this->__toString());
		}
		return $this->_execute();
	}

	/**
	 * @return $this
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Unimplemented
	 */
	public function execute(): self {
		$this->_execute();
		return $this;
	}

	/**
	 * Returns the inserted ID or null if non-primary key insert
	 *
	 * @return ?int
	 */
	public function result(): ?int {
		return $this->result;
	}

	/**
	 * @return $this
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Unimplemented
	 * @deprecated 2022-05
	 */
	public function exec(): self {
		return $this->execute();
	}
}
