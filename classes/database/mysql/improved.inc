<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/database/mysql/improved.inc $
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
class Database_MySQL_Improved extends Database_MySQL {
	protected $auto_reconnect = false;

	/**
	 * Database connection
	 *
	 * @var mysqli
	 */
	protected $Connection;

	/**
	 * (non-PHPdoc)
	 *
	 * @see Database::connection()
	 */
	final public function connection() {
		return $this->Connection;
	}

	// 		MYSQLI_CLIENT_COMPRESS	    Use compression protocol
	// 		MYSQLI_CLIENT_FOUND_ROWS	return number of matched rows, not the number of affected rows
	// 		MYSQLI_CLIENT_IGNORE_SPACE	Allow spaces after function names. Makes all function names reserved words.
	// 		MYSQLI_CLIENT_INTERACTIVE	Allow interactive_timeout seconds (instead of wait_timeout seconds) of inactivity before closing the connection
	// 		MYSQLI_CLIENT_SSL           Use SSL
	private static $flag_map = array(
		'compress' => MYSQLI_CLIENT_COMPRESS,
		'found rows' => MYSQLI_CLIENT_FOUND_ROWS,
		'ignore space' => MYSQLI_CLIENT_IGNORE_SPACE,
		'interactive' => MYSQLI_CLIENT_INTERACTIVE,
		'ssl' => MYSQLI_CLIENT_SSL
	);
	final protected function _mysql_connect($server, $user, $password, $database, $port) {
		$conn = mysqli_init(); //@new mysqli($server, $user, $password, $database, $port);
		if ($this->option_bool("infile")) {
			mysqli_options($conn, MYSQLI_OPT_LOCAL_INFILE, true);
		}
		mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, $this->option_integer('connect_timeout', 5));
		$flags = $this->option("connect_flags", 0);
		if (is_numeric($flags)) {
			$flags = intval($flags);
		} else if (is_string($flags) || is_array($flags)) {
			$flag_tokens = arr::change_value_case(to_list($flags));
			$flags = 0;
			foreach ($flag_tokens as $token) {
				if (!array_key_exists($token, self::$flag_map)) {
					log::warning("Unknown flag {token} in Database_MySQL_Improved::connect_flags: possible flags are {flags}", array(
						"token" => $token,
						"flags" => array_keys(self::$flag_map)
					));
				} else {
					$flags |= self::$flag_map[$token];
				}
			}
		} else {
			log::warning("Unknown connect_flags option value type passed to Database_MySQL_Improved {type}", array(
				"type" => gettype($flags)
			));
			$flags = 0;
		}
		if (!@mysqli_real_connect($conn, $server, $user, $password, $database, $port, null, $flags)) {
			$error = mysqli_connect_error();
			if ($error) {
				$this->_connection_error(compact("database", "server", "user", "port") + array(
					"error" => $error,
					"errno" => mysqli_connect_errno()
				));
			}
			log::error("Connection to database $user@$server:$port/$database FAILED, no connection error");
			return false;
		}
		$this->Connection = $conn;
		return true;
	}
	public function auto_reconnect($set = null) {
		if ($set === null) {
			return $this->auto_reconnect;
		}
		$this->auto_reconnect = to_bool($set);
		return $this;
	}
	public final function disconnect() {
		parent::disconnect();
		if ($this->Connection) {
			mysqli_close($this->Connection);
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
		if (!$this->Connection && avalue($options, 'auto_connect', $this->option_bool("auto_connect", true))) {
			$this->connect();
		}
		if (!$this->Connection) {
			throw new Database_Exception_Connect($this->URL, 0, "Not connected to database {safe_url} when attempting query: {sql}", array(
				"sql" => $query,
				"safe_url" => $this->safe_url("")
			));
		}
		$tries = 0;
		do {
			$query = $this->_query_before($query, $options);
			$result = @mysqli_query($this->Connection, $query);
			$this->_query_after($query, $options);
			if ($result) {
				return $result;
			}
			$message = mysqli_error($this->Connection);
			$errno = mysqli_errno($this->Connection);
			if ($errno === 2006 && $this->auto_reconnect) /* CR_SERVER_GONE_ERROR */ {
				log::warning("Reconnecting to database {url}", array(
					"url" => $this->safe_url()
				));
				$this->reconnect();
			} else {
				break;
			}
		} while (++$tries < 10);
		$this->_mysql_throw_error($query, $errno, $message);
	}
	public final function affected_rows($result = null) {
		if (is_resource($result)) {
			return mysqli_num_rows($result);
		}
		return mysqli_affected_rows($this->Connection);
	}
	public final function free($result) {
		if (empty($result)) {
			return;
		}
		mysqli_free_result($result);
	}
	public final function insert_id() {
		$id = mysqli_insert_id($this->Connection);
		if ($id == 0)
			return false;
		return $id;
	}
	public final function fetch_assoc($result) {
		return mysqli_fetch_assoc($result);
	}
	public final function fetch_array($result) {
		return mysqli_fetch_array($result, MYSQLI_NUM);
	}
	public final function native_quote_text($value) {
		if (!$this->Connection) {
			// Usually means the database is down
			return "'" . addslashes($value) . "'";
		}
		return "'" . mysqli_real_escape_string($this->Connection, $value) . "'";
	}
	public final function has_innodb() {
		$ver = mysqli_get_server_info($this->Connection);
		if (strpos($ver, "4.0") !== false) {
			return true;
		}
		return false;
	}
}

