<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/database/exception/schema.inc $
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 5:18 PM
 */

class Database_Exception_Schema extends Database_Exception {
	function __construct($db, $sql = "", $message = null, array $arguments = array(), $previous = null) {
		parent::__construct($db, $sql, -1, $message, $arguments, $previous);
	}
}

