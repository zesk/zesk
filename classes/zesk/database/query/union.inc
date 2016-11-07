<?php
/**
 * Database Query Union
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/zesk/database/query/union.inc $
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
class Database_Query_Union extends Database_Query_Select_Base {

	/**
	 * Array of queries to UNION
	 *
	 * @var array
	 */
	protected $queries = array();

	/**
	 * Order by clause
	 *
	 * @var string
	 */
	private $order_by = null;

	/**
	 * Construct a new UNION select query
	 *
	 * @param Database $db
	 */
	function __construct(Database $db = null) {
		parent::__construct("UNION", $db);
	}

	/**
	 * Create an new query
	 *
	 * @param string $db
	 * @return Database_Query_Select
	 */
	public static function factory($db = null) {
		return new Database_Query_Union($db);
	}
	function union(Database_Query_Select $select) {
		$this->queries[] = $select;
		return $this;
	}
	function what($what) {
		foreach ($this->queries as $query) {
			/* @var $query Database_Query_Select */
			$query->what($what);
		}
		return $this;
	}
	function from($table, $alias = "") {
		foreach ($this->queries as $query) {
			/* @var $query Database_Query_Select */
			$query->from($table, $alias);
		}
		return $this;
	}
	function join($sql, $join_id = null) {
		foreach ($this->queries as $query) {
			/* @var $query Database_Query_Select */
			$query->join($sql, $join_id);
		}
		return $this;
	}
	function where($k, $v = null) {
		foreach ($this->queries as $query) {
			/* @var $query Database_Query_Select */
			$query->where($k, $v);
		}
		return $this;
	}
	function group_by($group_by) {
		foreach ($this->queries as $query) {
			/* @var $query Database_Query_Select */
			$query->group_by($group_by);
		}
		return $this;
	}
	function order_by($order_by) {
		$this->order_by = $order_by;
		return $this;
	}
	function limit($offset = 0, $limit = null) {
		foreach ($this->queries as $query) {
			/* @var $query Database_Query_Select */
			$query->limit($offset, $limit);
		}
		return $this;
	}
	function __toString() {
		$sqls = array();
		foreach ($this->queries as $query) {
			/* @var $query Database_Query_Select */
			$sqls[] = $query->__toString();
		}
		return implode(" UNION ", arr::wrap($sqls, "(", ")")) . $this->sql()->order_by($this->order_by);
	}
}
