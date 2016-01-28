<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/database/exception/mysqli.inc $
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Tue Apr 06 10:53:22 EDT 2010 10:53:22
 */
class Database_Exception_MySQLi extends Database_Exception {
	function __construct($db, $sql = "") {
		if (is_object($db)) {
			parent::__construct($db, $sql, mysqli_errno($db), mysqli_error($db));
		} else {
			parent::__construct($db, $sql, 0, "Not connected");
		}
	}
}
