<?php
declare(strict_types=1);
/**
 * Database Query Union
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/Database/Query/Union.php $
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM;

use Throwable;
use zesk\ArrayTools;
use zesk\Database;
use zesk\Exception_Deprecated;
use zesk\PHP;

/**
 *
 * @author kent
 *
 */
class Database_Query_Union extends Database_Query_Select_Base {
	/**
	 * Array of queries to UNION
	 *
	 * @var Database_Query_Select[]
	 */
	protected array $queries = [];

	/**
	 * Order by clause
	 *
	 * @var array
	 */
	private array $order_by = [];

	/**
	 * Construct a new UNION select query
	 *
	 * @param Database $db
	 */
	public function __construct(Database $db) {
		parent::__construct('UNION', $db);
	}

	/**
	 * Create a new query
	 *
	 * @param Database $db
	 * @return Database_Query_Union
	 */
	public static function factory(Database $db): self {
		return new Database_Query_Union($db);
	}

	/**
	 * @param Database_Query_Select $select
	 * @return $this
	 */
	public function union(Database_Query_Select $select): self {
		$this->queries[] = $select;
		return $this;
	}

	/**
	 * @param string $alias
	 * @param string $member
	 * @return $this
	 */
	public function addWhat(string $alias, string $member = ''): self {
		foreach ($this->queries as $query) {
			$query->addWhat($alias, $member);
		}
		return $this;
	}

	/**
	 * @param string $table
	 * @param string $alias
	 * @return $this
	 */
	public function from(string $table, string $alias = ''): self {
		foreach ($this->queries as $query) {
			$query->from($table, $alias);
		}
		return $this;
	}

	/**
	 * @param string $sql
	 * @param string $join_id
	 * @return $this
	 * @throws Exception_Deprecated
	 * @deprecated 2022-05
	 */
	public function join(string $sql, string $join_id = ''): self {
		$this->application->deprecated(__METHOD__);
		return $this->addJoin($sql, $join_id);
	}

	/**
	 * @param string $join_sql
	 * @param string $join_id
	 * @return $this
	 */
	public function addJoin(string $join_sql, string $join_id = ''): self {
		foreach ($this->queries as $query) {
			$query->addJoin($join_sql, $join_id);
		}
		return $this;
	}

	/**
	 * @param string $k
	 * @param mixed $v
	 * @return $this
	 */
	public function addWhere(string $k, mixed $v): self {
		foreach ($this->queries as $query) {
			$query->addWhere($k, $v);
		}
		return $this;
	}

	/**
	 * @param array $group_by
	 * @return $this
	 */
	public function setGroupBy(array $group_by): self {
		foreach ($this->queries as $query) {
			$query->setGroupBy($group_by);
		}
		return $this;
	}

	/**
	 * @param string|array $order_by
	 * @return $this
	 */
	public function setOrderBy(string|array $order_by): self {
		$this->order_by = toList($order_by);
		return $this;
	}

	/**
	 * @param int $offset
	 * @param int $limit
	 * @return $this
	 */
	public function setOffsetLimit(int $offset = 0, int $limit = -1): self {
		foreach ($this->queries as $query) {
			$query->setOffsetLimit($offset, $limit);
		}
		return $this;
	}

	/**
	 * Convert to SQL
	 *
	 * @return string
	 */
	public function __toString(): string {
		try {
			return $this->toSQL();
		} catch (Throwable $e) {
			PHP::log($e);
			return '';
		}
	}

	/**
	 * Convert to SQL
	 *
	 * @return string
	 */
	public function toSQL(): string {
		$sql_phrases = [];
		foreach ($this->queries as $query) {
			$sql_phrases[] = $query->__toString();
		}
		return implode(' UNION ', ArrayTools::wrapValues($sql_phrases, '(', ')')) . $this->sql()->order_by($this->order_by);
	}
}
