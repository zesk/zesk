<?php declare(strict_types=1);
/**
 * Delete
 *
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
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
	protected $replace = false;

	/**
	 *
	 * @var Database_Query_Select
	 */
	protected $select = null;

	/**
	 * INSERT INTO {$this->into}
	 *
	 * @var string
	 */
	protected $into = null;

	/**
	 * Result
	 *
	 * @var mixed
	 */
	protected $result = null;

	/**
	 * Construct a new insert query
	 *
	 * @param Database $db
	 */
	public function __construct(Database $db) {
		parent::__construct('INSERT', $db);
	}

	/**
	 * Getter/setter for "into" which table
	 *
	 * @param string $set Into table name, null to get
	 * @return Database_Query_Insert|string
	 */
	public function into($set = null) {
		if ($set === null) {
			zesk()->deprecated('setter/getter changed to PSR');
			return $this->into;
		}
		$this->setTable($set);
		$this->into = $set;
		return $this;
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
	 * Set to replace mode
	 *
	 * @param null $set
	 * @return Database_Query_Insert|bool
	 */
	public function replace($set = null) {
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
	public function insert() {
		$this->replace = false;
		return $this;
	}

	/**
	 * Insert from a SELECT query
	 *
	 * @param Database_Query_Select $query
	 * @return Database_Query_Insert
	 */
	public function select(Database_Query_Select $query) {
		$this->select = $query;
		return $this;
	}

	/**
	 * Convert this query to SQL
	 *
	 * @return string
	 * @throws Exception_Parameter
	 */
	public function __toString() {
		$options = [
			'table' => $this->into,
			'values' => $this->values,
			'low_priority' => $this->low_priority,
		];
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

	/**
	 * @return bool|mixed
	 * @throws Exception_Parameter
	 */
	private function _execute() {
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
	 * @return mixed
	 * @throws Exception_Semantics|Exception_Parameter
	 */
	public function id() {
		if ($this->low_priority) {
			throw new Exception_Semantics('Can not execute query as low priority and retrieve id: ' . $this->__toString());
		}
		if ($this->select) {
			throw new Exception_Semantics('Can not execute query as select and retrieve id: ' . $this->__toString());
		}
		return $this->_execute();
	}

	/**
	 *
	 * @return mixed
	 * @throws Exception_Parameter
	 */
	public function execute() {
		return $this->_execute();
	}

	/**
	 *
	 * @return self
	 * @throws Exception_Parameter
	 */
	public function exec() {
		$this->_execute();
		return $this;
	}

	/**
	 *
	 * @return mixed
	 */
	public function result() {
		return $this->result;
	}
}
