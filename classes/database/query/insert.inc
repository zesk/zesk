<?php
/**
 * Delete
 *
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/database/query/insert.inc $
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
class Database_Query_Insert extends Database_Query_Edit {
	/**
	 * This is a REPLACE command
	 *
	 * @var boolean
	 */
	protected $replace = false;

	/**
	 *
	 * @var Database_Query_Select
	 */
	protected $select = null;

	/**
	 * Construct a new insert query
	 *
	 * @param Database $db
	 */
	function __construct(Database $db) {
		parent::__construct("INSERT", $db);
	}

	function into($table) {
		$this->table = $table;
		return $this;
	}

	/**
	 * Set to replace mode
	 *
	 * @return Database_Query_Insert
	 */
	function replace($set = null) {
		if (is_bool($set)) {
			$this->replace = $set;
			return $this;
		}
		return $this->replace;
	}

	/**
	 * Set to insert mode
	 *
	 * @return Database_Query_Insert
	 */
	function insert() {
		$this->replace = false;
		return $this;
	}

	/**
	 * Insert from a SELECT query
	 *
	 * @return Database_Query_Insert
	 */
	function select(Database_Query_Select $query) {
		$this->select = $query;
		return $this;
	}

	/**
	 * Convert this query to SQL
	 *
	 * @return string
	 */
	function __toString() {
		$options = array(
			'table' => $this->table,
			'values' => $this->values,
			'low_priority' => $this->low_priority
		);
		if ($this->replace) {
			$options['verb'] = 'REPLACE';
		}
		if ($this->select) {
			$options['values'] = $this->select->what();
			$options['select'] = strval($this->select);
			return $this->sql()->insert_select($options);
		}
		return $this->sql()->insert($options);
	}
	private function _execute($get_id) {
		if ($this->select) {
			$sql = $this->__toString();
			return $this->database()->query($sql);
		}
		$options = array(
			'table' => $this->table,
			'values' => $this->values
		);
		if ($this->replace) {
			$options['verb'] = 'REPLACE';
		}
		return $this->db->insert($this->table, $this->values, $options);
	}
	function id() {
		if ($this->low_priority) {
			throw new Exception_Semantics("Can not execute query as low priority and retrieve id: " . $this->__toString());
		}
		if ($this->select) {
			throw new Exception_Semantics("Can not execute query as select and retrieve id: " . $this->__toString());
		}
		return $this->_execute(true);
	}
	function execute() {
		return $this->_execute(!$this->low_priority);
	}
}
