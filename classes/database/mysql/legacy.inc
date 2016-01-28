<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/database/mysql/legacy.inc $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */

/**
 *
 * @package zesk
 * @subpackage system
 */
class Database_MySQL_Legacy extends Database_MySQL {

	/**
	 * Database connection
	 *
	 * @var mysqli
	 */
	protected $Connection = null;
	public function connection() {
		return $this->Connection;
	}
	final protected function _mysql_connect($server, $user, $password, $database, $port) {
		zesk::deprecated();
		if (!function_exists('mysql_connect')) {
			throw new Exception_Unsupported("MySQL extension is not installed as part of PHP");
		}
		$connect_server = $server;
		if ($port) {
			$connect_server .= ":$port";
		}
		$persist = $this->option("persistent");
		if (zesk::getb("Database_MySQL_Legacy::use_persistent", false)) {
			// PHPVERSION: PHP 4.2.0 "new_link"
			$conn = @mysql_pconnect($connect_server, $user, $password);
		} else {
			$conn = @mysql_connect($connect_server, $user, $password, true);
		}
		if (!$conn) {
			$this->_connection_error(compact("database", "server", "user", "port"));
		}
		mysql_select_db($this->Database, $conn);
		$this->Connection = $conn;
		return true;
	}
	public final function reconnect() {
		$this->connect();
	}
	public final function disconnect() {
		parent::disconnect();
		if (is_resource($this->Connection)) {
			mysql_close($this->Connection);
			$this->Connection = false;
		}
	}
	public final function query($query, array $options = array()) {
		if (is_array($query)) {
			$result = array();
			foreach ($query as $index => $sql) {
				$result[$index] = $this->query($sql, $options);
			}
			return $result;
		}
		if (!$this->Connection && $this->option_bool("auto_connect", true)) {
			$this->connect();
		}
		if (!$this->Connection) {
			throw new Database_Exception_MySQL(null, "Database_MySQL::query: Not connected");
		}
		$query = $this->_query_before($query, $options);
		$result = mysql_query($query, $this->Connection);
		if (!$result) {
			$message = mysql_error($this->Connection);
			$errno = mysql_errno($this->Connection);
			$this->_mysql_throw_error($query, $errno, $message);
			throw new Database_Exception_MySQL($this->Connection, $query);
		}
		$this->_query_after($query, $options);
		return $result;
	}
	public final function affected_rows($result = null) {
		if (is_resource($result)) {
			return mysql_num_rows($result);
		}
		return mysql_affected_rows($this->Connection);
	}
	public final function free($result) {
		if (empty($result)) {
			return;
		}
		mysql_free_result($result);
	}
	public final function insert_id() {
		$id = mysql_insert_id($this->Connection);
		if ($id == 0) {
			return false;
		}
		return $id;
	}
	public function fetch_array($result) {
		return mysql_fetch_array($result, MYSQL_NUM);
	}
	public function fetch_assoc($result) {
		return mysql_fetch_assoc($result);
	}
	public final function hasInnoDB() {
		$ver = mysql_get_server_info($this->Connection);
		if (strpos($ver, "4.0") !== false) {
			return true;
		}
		return false;
	}
	public final function native_quote_text($value) {
		if ($this->Connection) {
			return "'" . mysql_real_escape_string($value, $this->Connection) . "'";
		} else {
			// Prevents PHP Warning:  mysql_real_escape_string() expects parameter 2 to be resource, boolean given in /Users/kent/ma/zesk/classes/database/mysql/legacy.inc on line 119
			return "'" . mysql_real_escape_string($value) . "'";
		}
	}
	public final function has_innodb() {
		$ver = mysql_get_server_info($this->Connection);
		if (strpos($ver, "4.0") !== false) {
			return true;
		}
		return false;
	}
}
