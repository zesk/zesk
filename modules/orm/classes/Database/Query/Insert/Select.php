<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage database
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

namespace zesk;

/**
 * @see Database_Query_Insert
 * @author kent
 *
 */
class Database_Query_Insert_Select extends Database_Query_Select {
	/**
	 * Low priority insert/replace
	 *
	 * @var boolean
	 */
	protected bool $low_priority = false;

	/**
	 *
	 * @var string
	 */
	private string $into = '';

	/**
	 *
	 * @var array
	 */
	protected array $what = [];

	/**
	 *
	 * @var string
	 */
	protected string $verb = 'INSERT';

	/**
	 * Create an new query
	 *
	 * @return Database_Query_Insert_Select
	 */
	public static function factory(Database $db): self {
		return new Database_Query_Insert_Select($db);
	}

	/**
	 *
	 * @param Database_Query_Select $query
	 * @return \zesk\Database_Query_Insert_Select
	 */
	public static function fromSelect(Database_Query_Select $query) {
		$new = self::factory($query->database());
		$new->copy_from($query);
		return $new;
	}

	/**
	 * Getter/setter for low priority state of this query
	 * @param boolean $low_priority
	 * @return boolean Database_Query_Edit
	 * @deprecated 2022-01
	 */
	public function low_priority($low_priority = null): bool {
		if ($low_priority === null) {
			return $this->low_priority;
		}
		$this->setLowPriority(to_bool($low_priority));
		return $this->low_priority;
	}

	/**
	 * Getter for low priority state of this query
	 * @param boolean $low_priority
	 * @return boolean Database_Query_Edit
	 */
	public function lowPriority(): bool {
		return $this->low_priority;
	}

	/**
	 * Setter for low priority state of this query
	 *
	 * @param boolean $low_priority
	 * @return self
	 */
	public function setLowPriority(bool $low_priority = true): self {
		$this->low_priority = $low_priority;
		return $this;
	}

	/**
	 * Getter for replace verb
	 *
	 * @return bool
	 */
	public function replace(): bool {
		return $this->verb === 'REPLACE';
	}

	/**
	 * Getter/setter for replace verb
	 *
	 * @param boolean $replace
	 * @return string|\zesk\Database_Query_Insert_Select
	 */
	public function setReplace(bool $replace = true): self {
		$this->verb = $replace ? 'REPLACE' : 'INSERT';
		return $this;
	}

	/**
	 *
	 * @param string $table
	 * @return \zesk\Database_Query_Insert_Select
	 */
	public function into(string $table): self {
		$this->into = $table;
		return $this;
	}

	/**
	 * @param string $what
	 * @return Database_Query_Select
	 * @throws Exception_Semantics
	 */
	public function setWhatString(string $what): self {
		throw new Exception_Semantics("{class} must have an associative array for what (passed in \"$what\")", ['class' => __CLASS__]);
	}

	public function __toString() {
		return $this->sql()->insert_select([
			'verb' => $this->verb,
			'table' => $this->into,
			'values' => $this->what,
			'low_priority' => $this->low_priority,
			'select' => parent::__toString(),
		]);
	}

	public function execute() {
		return $this->database()->query($this->__toString());
	}

	/**
	 *
	 * @param Database_Query_Select $query
	 * @return \zesk\Database_Query_Insert_Select
	 * @deprecated 2022-01
	 */
	public static function from_database_query_select(Database_Query_Select $query) {
		return self::fromSelect($query);
	}
}
