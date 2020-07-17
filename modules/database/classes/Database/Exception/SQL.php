<?php
/**
 * $Id$
 *
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Exception_SQL extends Database_Exception {
	/**
	 * KMD - Yes this is duplicated for now in parent class until we deprecate this.
	 *
	 * @deprecated 2020-07
	 * @var Database
	 */
	public $db = null;

	/**
	 *
	 * @var string
	 */
	public $sql = "";

	/**
	 *
	 * @param Database $db
	 * @param string $sql
	 * @param number $errno
	 * @param unknown $message
	 * @param array $arguments
	 * @param unknown $previous
	 */
	public function __construct(Database $db, $sql = "", $message = null, array $arguments = array(), $errno = 0, $previous = null) {
		$this->sql = $sql;
		$this->db = $db;

		$message = "Message: $message\nDatabase: " . $this->db->code_name() . "\nSQL: " . rtrim($this->sql) . "\n";
		parent::__construct($db, $message, $arguments, $errno, $previous);
	}

	/**
	 *
	 * @return string
	 */
	public function sql() {
		return $this->sql;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Exception::__toString()
	 */
	public function __toString() {
		$result = parent::__toString();
		$result .= "Error Number: " . $this->getCode() . "\n\n";
		return $result;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see zesk\Exception::variables()
	 */
	public function variables() {
		return array(
			'errno' => $this->getCode(),
			'db' => $this->db, // Deprecated
			'database' => $this->db,
			'database_code_name' => $this->db->code_name(),
			'sql' => $this->sql,
		) + parent::variables();
	}
}
