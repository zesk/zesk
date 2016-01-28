<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/database/exception/mysql.inc $
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 *            Created on Tue Apr 06 10:53:22 EDT 2010 10:53:22
 */
class Database_Exception_MySQL extends Database_Exception {
	function __construct($db, $sql = "", $errno = null, $message = null) {
		if (is_resource($db)) {
			if ($errno === null) {
				$errno = mysql_errno($db);
			}
			if ($message === null) {
				$message = mysql_error($db);
			}
			parent::__construct($db, $sql, $errno, $message);
		} else {
			parent::__construct($db, $sql, $errno, $message ? $message : "Not connected");
		}
	}
}
