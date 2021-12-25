<?php declare(strict_types=1);
/**
 * Database Query Union
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/Database/Query/Union.php $
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
	protected $queries = [];

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
	public function __construct(Database $db) {
		parent::__construct("UNION", $db);
	}

	/**
	 * Create an new query
	 *
	 * @param Database $db
	 * @return Database_Query_Union
	 */
	public static function factory(Database $db = null) {
		return new Database_Query_Union($db);
	}

	/**
	 * @param Database_Query_Select $select
	 * @return $this
	 */
	public function union(Database_Query_Select $select) {
		$this->queries[] = $select;
		return $this;
	}

	/**
	 * @param $what
	 * @return $this
	 * @throws Exception_Parameter
	 */
	public function what($what) {
		foreach ($this->queries as $query) {
			/* @var $query Database_Query_Select */
			$query->what($what);
		}
		return $this;
	}

	/**
	 * @param string $table
	 * @param string $alias
	 * @return $this
	 */
	public function from($table, $alias = "") {
		foreach ($this->queries as $query) {
			/* @var $query Database_Query_Select */
			$query->from($table, $alias);
		}
		return $this;
	}

	/**
	 * @param string $sql
	 * @param string $join_id
	 * @return $this
	 */
	public function join($sql, $join_id = null) {
		foreach ($this->queries as $query) {
			/* @var $query Database_Query_Select */
			$query->join($sql, $join_id);
		}
		return $this;
	}

	/**
	 * @param string|array $k
	 * @param mixed $v
	 * @return $this
	 */
	public function where($k, $v = null) {
		foreach ($this->queries as $query) {
			/* @var $query Database_Query_Select */
			$query->where($k, $v);
		}
		return $this;
	}

	/**
	 * @param string $group_by
	 * @return $this
	 */
	public function group_by($group_by) {
		foreach ($this->queries as $query) {
			/* @var $query Database_Query_Select */
			$query->group_by($group_by);
		}
		return $this;
	}

	/**
	 * @param string|array  $order_by
	 * @return $this
	 */
	public function order_by($order_by) {
		$this->order_by = $order_by;
		return $this;
	}

	/**
	 * @param int $offset
	 * @param int $limit
	 * @return $this
	 */
	public function limit($offset = 0, $limit = null) {
		foreach ($this->queries as $query) {
			/* @var $query Database_Query_Select */
			$query->limit($offset, $limit);
		}
		return $this;
	}

	/**
	 * Convert to SQL
	 *
	 * @return string
	 */
	public function __toString() {
		$sql_phrases = [];
		foreach ($this->queries as $query) {
			/* @var $query Database_Query_Select */
			$sql_phrases[] = $query->__toString();
		}
		return implode(" UNION ", ArrayTools::wrap($sql_phrases, "(", ")")) . $this->sql()->order_by($this->order_by);
	}
}
