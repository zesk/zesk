<?php
class Database_Query_Delete extends Database_Query {
	/**
	 * Where clause
	 *
	 * @var array
	 */
	protected $where = array();

	/**
	 * Store affected rows after execute
	 *
	 * @var integer
	 */
	protected $affected_rows = null;

	/**
	 * Store affected rows after execute
	 *
	 * @var integer
	 */
	protected $truncate = false;

	/**
	 * Construct a delete query
	 *
	 * @param Database $db
	 */
	function __construct(Database $db) {
		parent::__construct("DELETE", $db);
	}

	function truncate($set = null) {
		if ($set !== null) {
			$set = to_bool($set);
			if ($set === true && (count($this->where) > 0)) {
				log::warning("Failed to add truncate with a where clause", array(
					"query" => $this
				));
				return null;
			}
			$this->truncate = $set;
			return $this;
		}
		return $this->truncate;
	}
	/**
	 * Add where clause
	 *
	 * @param mixed $k
	 * @param string $v
	 * @return Database_Query_Select
	 */
	function where($k, $v = null) {
		if (is_array($k)) {
			$this->where = array_merge($this->where, $k);
		} else if ($k === null && is_string($v)) {
			$this->where[] = $v;
		} else if (!empty($k)) {
			$this->where[$k] = $v;
		}
		if ($this->truncate) {
			log::warning("Adding where clause de-activates truncate", array(
				"query" => $this
			));
			$this->truncate = false;
		}
		return $this;
	}
	function __toString() {
		$table = Object::class_table_name($this->class);
		return $this->sql()->delete(array(
			'table' => $table,
			'truncate' => $this->truncate,
			'where' => $this->where
		));
	}
	function affected_rows() {
		return $this->affected_rows;
	}
	function execute() {
		$db = $this->database();
		$result = $db->query($this->__toString());
		if ($result) {
			$this->affected_rows = $db->affected_rows($result);
		}
		if (is_bool($result)) {
			return $result ? $this : null;
		}
		return $result;
	}
}
