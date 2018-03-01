<?php
/**
 * Delete
 *
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Query_Update extends Database_Query_Edit {
	
	/**
	 * Update where clause
	 *
	 * @var array
	 */
	protected $where = array();
	
	/**
	 *
	 * @var resource
	 */
	private $result = null;
	
	/**
	 *
	 * @var boolean
	 */
	protected $ignore_constraints = false;
	
	/**
	 * Create a new UPDATE query
	 *
	 * @param Database $db
	 */
	function __construct(Database $db) {
		parent::__construct("UPDATE", $db);
	}
	
	/**
	 * Getter/setter for ignore contstraints flag for update
	 *
	 * @param boolean|null $set
	 * @return \zesk\Database_Query_Update|boolean
	 */
	function ignore_constraints($set = null) {
		if (is_bool($set)) {
			$this->ignore_constraints = true;
			return $this;
		}
		return $this->ignore_constraints;
	}
	/**
	 * Convert this query to SQL
	 *
	 * @return string
	 */
	function __toString() {
		return $this->database()->sql()->update(array(
			'table' => $this->table,
			'values' => $this->values,
			'where' => $this->where,
			'low_priority' => $this->low_priority
		));
	}
	
	/**
	 * Return the number of affected rows after query has run
	 *
	 * @return integer
	 */
	public final function affected_rows() {
		return $this->database()->affected_rows($this->result);
	}
	
	/**
	 *
	 * @return resource
	 */
	function result() {
		return $this->result;
	}
	
	/**
	 * @deprecated 2018-02 Use "execute()->result()" instead.
	 *
	 * @return self
	 */
	function exec() {
		zesk()->deprecated();
		return $this->execute();
	}
	/**
	 * Run this query
	 *
	 *
	 * @return boolean
	 */
	function execute() {
		$this->result = $this->database()->update($this->table, $this->values, $this->where, array(
			"low_priority" => $this->low_priority,
			"ignore_constraints" => $this->ignore_constraints
		));
		return $this;
	}
	
	/**
	 * Add where clause.
	 * Once traits are standard, make this a trait for SELECT/INSERT
	 *
	 * @trait
	 *
	 * @param mixed $k
	 * @param string $v
	 * @return Database_Query_Update
	 */
	function where($k, $v = null) {
		if (is_array($k)) {
			$this->where = array_merge($this->where, $k);
		} else if ($k === null && is_string($v)) {
			$this->where[] = $v;
		} else if (!empty($k)) {
			$this->where[$k] = $v;
		}
		return $this;
	}
}
