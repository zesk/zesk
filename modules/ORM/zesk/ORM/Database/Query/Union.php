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

namespace zesk\ORM\Database\Query;

use Throwable;
use zesk\Database\Base;
use zesk\ArrayTools;
use zesk\PHP;
use zesk\Types;

/**
 *
 * @author kent
 *
 */
class Union extends SelectBase {
	/**
	 * Array of queries to UNION
	 *
	 * @var Select[]
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
	 * @param Base $db
	 */
	public function __construct(Base $db) {
		parent::__construct('UNION', $db);
	}

	/**
	 * Create a new query
	 *
	 * @param Base $db
	 * @return self
	 */
	public static function factory(Base $db): self {
		return new self($db);
	}

	/**
	 * @param Select $select
	 * @return $this
	 */
	public function union(Select $select): self {
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
		$this->order_by = Types::toList($order_by);
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
		return implode(' UNION ', ArrayTools::wrapValues($sql_phrases, '(', ')')) . $this->sql()->orderBy($this->order_by);
	}
}
