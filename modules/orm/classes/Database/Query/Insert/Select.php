<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage database
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

use zesk\Database\QueryResult;

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
	 * Getter for low priority state of this query
	 * @return boolean
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
	 * @return self
	 */
	public function setReplace(bool $replace = true): self {
		$this->verb = $replace ? 'REPLACE' : 'INSERT';
		return $this;
	}

	/**
	 *
	 * @param string $table
	 * @return self
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
		return $this->sql()->insert_select($this->into, $this->what, parent::__toString(), [
			'verb' => $this->verb,
			'low_priority' => $this->low_priority,
		]);
	}

	/**
	 * @return QueryResult
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 */
	public function execute(): QueryResult {
		return $this->database()->query($this->__toString());
	}
}
