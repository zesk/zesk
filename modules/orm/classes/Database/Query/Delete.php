<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Query_Delete extends Database_Query {
	/**
	 * Where clause
	 *
	 * @var array
	 */
	protected $where = [];

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
	 *
	 * @var mixed
	 */
	protected $result = null;

	/**
	 * Construct a delete query
	 *
	 * @param Database $db
	 */
	public function __construct(Database $db) {
		parent::__construct("DELETE", $db);
	}

	public function truncate($set = null) {
		if ($set !== null) {
			$set = to_bool($set);
			if ($set === true && (count($this->where) > 0)) {
				$this->application->logger->warning("Failed to add truncate with a where clause", [
					"query" => $this,
				]);
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
	 * @return Database_Query_Delete
	 */
	public function where($k, $v = null) {
		if (is_array($k)) {
			$this->where = array_merge($this->where, $k);
		} elseif ($k === null && is_string($v)) {
			$this->where[] = $v;
		} elseif (!empty($k)) {
			$this->where[$k] = $v;
		}
		if ($this->truncate) {
			$this->application->logger->warning("Adding where clause de-activates truncate", [
				"query" => $this,
			]);
			$this->truncate = false;
		}
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function __toString() {
		$table = $this->application->orm_registry($this->class)->table();
		return $this->sql()->delete([
			'table' => $table,
			'truncate' => $this->truncate,
			'where' => $this->where,
		]);
	}

	public function affected_rows() {
		return $this->affected_rows;
	}

	/**
	 * Execute syntax is now identical, stop using this method, use ->execute() with new semantics
	 *
	 * @return Database_Query_Delete
	 * @throws Database_Exception|Exception_Deprecated
	 * @deprecated 2018-02
	 * @see self::execute()
	 */
	public function exec() {
		zesk()->deprecated();
		return $this->execute();
	}

	/**
	 *
	 * @return mixed
	 */
	public function result() {
		return $this->result;
	}

	/**
	 * @return Database_Query_Delete|NULL|mixed
	 */
	private function _execute() {
		$db = $this->database();
		$result = $this->result = $db->query($this->__toString());
		if ($result) {
			$this->affected_rows = $db->affected_rows($result);
		} else {
			$this->affected_rows = null;
		}
		if (is_bool($result)) {
			return $result ? $this : null;
		}
		return $result;
	}

	/**
	 * Prefer this function name, but need to change semantics so will remove and then rename ->exec
	 * to ->execute later.
	 * Use ->exec()->result() to get similar behavior in the short term
	 *
	 * @return Database_Query_Delete|NULL|mixed
	 * @throws Database_Exception
	 */
	public function execute() {
		if ($this->_execute() === null) {
			throw new Database_Exception($this->database(), "Delete query failed: {sql}", [
				"sql" => $this->__toString(),
			]);
		}
		return $this;
	}
}
