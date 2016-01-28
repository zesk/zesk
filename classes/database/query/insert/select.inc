<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/database/query/insert/select.inc $
 * @package zesk
 * @subpackage database
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

/**
 * @see Database_Query_Insert
 * @author kent
 *
 */
class Database_Query_Insert_Select extends Database_Query_Select {

	/**
	 * Low priority update/insert
	 *
	 * @var boolean
	 */
	protected $low_priority = false;

	private $into = null;

	protected $what = array();

	/**
	 * Create an new query
	 *
	 * @param string $db
	 * @return Database_Query_Insert_Select
	 */
	public static function factory(Database $db = null) {
		return new Database_Query_Insert_Select($db);
	}

	/**
	 * Getter/setter for low priority state of this query
	 *
	 * @param boolean $low_priority
	 * @return boolean Database_Query_Edit
	 */
	function low_priority($low_priority = null) {
		if ($low_priority === null) {
			return $this->low_priority;
		}
		$this->low_priority = to_bool($low_priority);
		return $this;
	}

	function into($table) {
		$this->into = $table;
		return $this;
	}
	function what($mixed = null, $value = null) {
		if (is_string($mixed) && $value === null) {
			throw new Exception_Semantics("Database_Query_Insert_Select must have an associative array for what (passed in \"$mixed\")");
		}
		return parent::what($mixed, $value);
	}
	function __toString() {
		return $this->sql()->insert_select(array(
			"table" => $this->into,
			"values" => $this->what,
			"low_priority" => $this->low_priority,
			"select" => parent::__toString()
		));
	}

	function execute() {
		return $this->database()->query($this->__toString());
	}
}
