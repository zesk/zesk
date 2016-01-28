<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/database/exception.inc $
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 *            Created on Tue Apr 06 10:53:22 EDT 2010 10:53:22
 */
class Database_Exception extends Zesk_Exception {
	public $errno = 0;
	public $db = null;
	public $sql = "";
	function __construct($db, $sql = "", $errno = 0, $message = null, array $arguments = array(), $previous = null) {
		if ($message === null) {
			$message = $sql;
		} else if (!empty($sql)) {
			$message .= "\nSQL: $sql";
		}
		parent::__construct($message, $arguments, $errno, $previous);
		$this->errno = $errno;
		$this->db = $db instanceof Database ? $db->connection() : is_resource($db) ? $db : null;
		$this->sql = $sql ? $sql : avalue($arguments, 'sql');
	}
	function __toString() {
		$result = parent::__toString();
		$result .= "MySQL Error: $this->errno\n\n";
		$result .= "SQL: $this->sql\n\n";
		return $result;
	}
	function variables() {
		return array(
			'errno' => $this->errno, 
			'db' => $this->db, 
			'sql' => $this->sql
		) + parent::variables();
	}
}
