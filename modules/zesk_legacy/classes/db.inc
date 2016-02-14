<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/zesk_legacy/classes/db.inc $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @package zesk
 * @subpackage model
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

zesk::deprecated();

/**
 * @deprecated Database instead
 * @author kent
 */
class db {

	static $_dolog = null;

	static $debug = false;

	static $dbs = array();

	static $db_urls = array();

	static $db_default = "";

	static $auto_table_names = false;

	static $auto_table_names_options = null;

	private static $table_name_cache = array();

	public static function debug($set = null) {
		$value = self::$debug;
		if (is_bool($set)) {
			self::$debug = $set;
		} else if (is_string($set)) {
			self::$_dolog = $set;
		}
		return $value;
	}

	public static function die_on_error($set = null) {
		if (is_bool($set)) {
			zesk::set('DB_DIE_ON_ERROR', $set);
		}
		return zesk::getb('DB_DIE_ON_ERROR', true);
	}

	/*
	 * Query Profile Tools
	 */
	private static function mysql_read_rnd_state() {
		$read_rnd_results = array();
		$res = mysql_query("SHOW STATUS LIKE 'Handler_read_rnd%'");
		while (($row = mysql_fetch_assoc($res)) !== false) {
			$read_rnd_results[$row['Variable_name']] = intval($row['Value']);
		}
		mysql_free_result($res);
		return $read_rnd_results;
	}
	/*
	 * Generic Query Tools
	 */
	private static function log($message) {
		error_log("-|" . str_replace("\n", "\\n", $message) . "\n", 3, zesk::site_root("db-query.log"));
	}

	public static function last_error() {
		return zesk::get('db::last_error');
	}

	public static function auto_table_names($set = null) {
		if ($set !== null) {
			self::$auto_table_names = to_bool($set);
		}
		return self::$auto_table_names;
	}

	public static function auto_table_names_options($set = null) {
		if (is_array($set)) {
			self::$auto_table_names_options = $set;
		}
		return self::$auto_table_names_options;
	}

	public static function unstring($sql, &$state) {
		$unstrung = strtr($sql, array(
			"\\'" => chr(1)
		));
		$matches = null;
		if (!preg_match_all("/'[^']*'/s", $unstrung, $matches, PREG_PATTERN_ORDER)) {
			return $sql;
		}
		$state = array();
		// When $replace is a long string, say, 29000 characters or more, can not do array_flip
		// PHP has a limit on the key size, so strtr inline below
		foreach ($matches[0] as $index => $match) {
			$search = "#\$%$index%\$#";
			$replace = strtr($match, array(
				chr(1) => "\\'"
			));
			$state[$search] = $replace;
			$sql = str_replace($replace, $search, $sql);
		}
		return $sql;
	}

	public static function restring($sql, $state) {
		if (!is_array($state)) {
			return $sql;
		}
		return strtr($sql, $state);
	}

	public static function auto_table_names_replace($sql) {
		if (is_array($sql)) {
			foreach ($sql as $k => $v) {
				$sql[$k] = self::auto_table_names_replace($v);
			}
			return $sql;
		}
		$matches = false;
		$state = null;
		$sql = self::unstring($sql, $state);
		$sql = strtr($sql, self::$table_name_cache);
		if (!preg_match_all('/\{([A-Za-z][A-Za-z0-9_]*)\}/', $sql, $matches, PREG_SET_ORDER)) {
			return self::restring($sql, $state);
		}
		$options = self::$auto_table_names_options;
		$map = self::$table_name_cache;
		foreach ($matches as $match) {
			list($full_match, $class) = $match;
			$table = Object::class_table_name($class, null, $options);
			if ($options === null) {
				self::$table_name_cache[$full_match] = $table;
			}
			$map[$full_match] = $table;
		}
		$sql = strtr($sql, $map);
		return self::restring($sql, $state);
	}

	private static function _query($sql, $dbname = "") {
		$db = self::connect($dbname);
		if (!$db) {
			if (self::debug()) {
				die("Can't connect to database \"$dbname\"\n");
			}
			$rd = zesk::get("database_fail_http::redirect");
			if ($rd) {
				header("Location: $rd");
				exit();
			}
			return false;
		}
		if (self::debug()) {
			echo "$dbname (" . strval($db) . "): $sql" . newline();
		}
		if (zesk::getb('DB_PROFILE')) {
			$read_rnd = self::mysql_read_rnd_state();
		}
		$dolog = false;
		if (zesk::getb("DB_LOGGING") || zesk::getb('FireBug') || self::$_dolog) {
			$t = new Timer();
			$dolog = true;
		}
		if (is_object($sql)) {
			// Support PHP < 5.2.0
			$sql = $sql->__toString();
		}
		if (self::$auto_table_names) {
			$sql = self::auto_table_names_replace($sql);
		}
		$unbuffered = self::unbuffered();
		$result = $unbuffered ? $db->query_unbuffered($sql, $db) : $db->query($sql);
		if ($dolog) {
			$elapsed = $t->elapsed();
			log::send("SQL: {sql} ({elapsed})", array(
				'sql' => $sql,
				'unbuffered' => $unbuffered,
				'elapsed' => $elapsed
			), $elapsed > 0.1 ? log::WARNING : log::DEBUG);
			// if (zesk::getb("DB_LOGGING")) {
			// error_log($t->elapsed() . "|" . str_replace("\n", "\\n", $sql) . "\n", 3,
			// zesk::site_root("db-query.log"));
			// }
			// if (self::$_dolog === "html") {
			// $elapsed = $t->elapsed();
			// $html = html::specialchars("$elapsed: $sql");
			// if ($elapsed > 0.1) {
			// $html = html::tag('span', '.red', $html);
			// }
			// html::log($html);
			// }
		}
		if (zesk::getb('DB_PROFILE')) {
			$read_rnd2 = self::mysql_read_rnd_state();
			$profile_log = array();
			foreach ($read_rnd2 as $k => $v) {
				$delta = $v - $read_rnd[$k] - 6; // SHOW STATUS LIKE increases this value, too
				if ($delta > zesk::geti('DB_PROFILE_THRESHOLD', 10)) {
					$profile_log[] = "$delta increase in $k";
				}
			}
			if (count($profile_log)) {
				$clean_sql = str_replace("\n", "\\n", str_replace("\t", "\\t", $sql));
				$suffix = " for query $clean_sql\n";
				error_log(implode($suffix, $profile_log) . $suffix, 3, zesk::site_root("db-profile.log"));
			}
		}
		return $result;
	}

	public static function result_count($res) {
		return mysql_num_rows($res);
	}

	public static function fetch_array(Database $db, $res, $k = false, $v = false, $default = null) {
		if (!$res) {
			return ($default === null) ? array() : $default;
		}
		if ($k === null) {
			$k = false;
		}
		if ($v === null) {
			$v = false;
		}
		$result = array();
		while (is_array($row = $db->fetch_assoc($res))) {
			if ($k !== false) {
				if ($v !== false) {
					$result[avalue($row, $k)] = avalue($row, $v);
				} else {
					$result[avalue($row, $k)] = $row;
				}
			} else {
				if ($v !== false) {
					$result[] = avalue($row, $v);
				} else {
					$result[] = $row;
				}
			}
		}
		$db->free($res);
		return $result;
	}

	private static function fetch_array_index(Database $db, $res, $k = false, $v = false, $default = null) {
		if (!$res) {
			return ($default === null) ? array() : $default;
		}
		$result = array();
		while (is_array($row = $db->fetch_array($res))) {
			if ($k !== false) {
				if ($v !== false) {
					$result[avalue($row, $k)] = avalue($row, $v);
				} else {
					$result[avalue($row, $k)] = $row;
				}
			} else {
				if ($v !== false) {
					$result[] = avalue($row, $v);
				} else {
					$result[] = $row;
				}
			}
		}
		$db->free($res);
		return $result;
	}

	private static function _query_one(Database $db, $res, $field, $default) {
		if (!$res) {
			return $default;
		}
		$row = $db->fetch_assoc($res);
		if (!is_array($row)) {
			return $default;
		}
		if ($field === false || $field === null) {
			return $row;
		}
		return avalue($row, $field, $default);
	}

	private static function _insert($t, $arr, $low_priority = false, $dbname = "") {
		$sql = sql::insert($t, $arr, $low_priority, $dbname);
		return self::_query($sql, $dbname);
	}

	private static function _replace($t, $arr, $low_priority = false, $dbname = "") {
		$sql = sql::replace($t, $arr, $low_priority, $dbname);
		return self::_query($sql, $dbname);
	}

	/*
	 * ====================================================================================*\ Query Tools
	 * \*------------------------------------------------------------------------------------
	 */
	public static function connect_reset($close_connection = true) {
		foreach (self::$dbs as $db) {
			if ($db instanceof Database) {
				$db->disconnect();
			}
		}
		self::$dbs = array();
	}

	public static function register($name, $url, $is_default = false) {
		Database::register($name, $url, $is_default);
	}

	public static function dump() {
		echo "db:\n";
		debug::output(self::$dbs);
		echo "db_urls:\n";
		debug::output(self::$db_urls);
		echo "db-default:\n";
		debug::output(self::$db_default);
		echo "\n";
	}

	public static function url($name = "", $url = null, $is_default = false) {
		$name = strtolower($name);
		if ($url !== null) {
			self::register($name, $url, $is_default);
		}
		$url = avalue(self::$db_urls, $name);
		if (!empty($url)) {
			return $url;
		}
		$global_suffix = empty($name) ? "" : "_" . strtoupper($name);
		$url = zesk::get('DB_URL' . $global_suffix);
		return $url;
	}

	/**
	 * Connect to the database
	 * @param string $name
	 * @param string $url
	 * @return Database
	 */
	public static function connect($name = "", $url = null, $dbselect = true) {
		if (empty($name)) {
			$name = self::$db_default;
		} else {
			$name = strtolower($name);
		}
		$db = avalue(self::$dbs, $name);
		if ($db === false) {
			return null;
		}
		if ($db) {
			return $db;
		}
		if (empty($url)) {
			$url = self::url($name, $url);
		}
		$link = Database::factory($url);
		if ($link) {
			self::$dbs[$name] = $link;
		} else {
			self::$dbs[$name] = false;
		}
		return $link;
	}

	public static function connection_id($dbname = null) {
		return to_integer(self::query_one("SELECT CONNECTION_ID() AS X", "X", false, false, $dbname), null);
	}

	public static function unbuffered($set = null) {
		if (is_bool($set)) {
			zesk::set('DB_UNBUFFERED', $set);
		}
		return zesk::getb('DB_UNBUFFERED');
	}

	public static function query($sql, $dbname = "") {
		if (is_array($sql)) {
			$result = array();
			foreach ($sql as $k => $q) {
				$q = trim($q);
				if (empty($q))
					continue;
				$result[$k] = self::_query($q, $dbname);
			}
			return $result;
		}
		return self::_query($sql, $dbname);
	}

	public static function query_one($sql, $field = false, $default = false, $dbname = "") {
		$db = db::connect($dbname);
		if (!$db) {
			return $default;
		}
		return $db->query_one($sql, $field, $default);
	}

	public static function query_integer($sql, $field = false, $default = 0, $dbname = "") {
		$result = self::query_one($sql, $field, null, $dbname);
		if ($result === null) {
			return $default;
		}
		return intval($result);
	}

	public static function query_array($sql, $k = null, $v = null, $default = null, $dbname = "") {
		$db = db::connect($dbname);
		return $db->query_array($sql, $k, $v, $default);
	}

	public static function query_array_index($sql, $k = null, $v = null, $default = null, $dbname = "") {
		$db = db::connect($dbname);
		return $db->query_array_index($sql, $k, $v, $default);
	}

	public static function query_object($sql, $k = false, $phpClass = false, $options = false, $dbname = "") {
		$db = db::connect($dbname);
		$arr = self::fetch_array($db, $db->query($sql), $k);
		if (is_array($arr)) {
			foreach ($arr as $k => $v) {
				$arr[$k] = Object::factory($phpClass, $v, $options);
			}
		}
		return $arr;
	}

	public static function insert($t, $arr, $get_id = true, $dbname = "") {
		if (!self::_insert($t, $arr, false, $dbname))
			return false;
		if ($get_id)
			return self::insert_id();
		return true;
	}

	public static function replace($t, $arr, $get_id = true, $dbname = "") {
		if (!self::_replace($t, $arr, false, $dbname))
			return false;
		if ($get_id)
			return self::insert_id();
		return true;
	}

	public static function insert_low($t, $arr, $dbname = "") {
		if (!self::_insert($t, $arr, true, $dbname)) {
			return false;
		}
		return true;
	}

	/**
	 * Update a database table
	 * @param string $table
	 * @param array $what
	 * @param array $where
	 * @param boolean $low_priority
	 * @param string $dbname
	 * @return boolean
	 */
	public static function update($table, $what, $where, $low_priority = false, $dbname = "") {
		$sql = sql::update($table, $what, $where, array(
			"update suffix" => $low_priority ? " low priority " : ""
		));
		return self::_query($sql, $dbname);
	}

	public static function update_id($t, $arr, $id, $idname = "ID", $low_priority = false, $dbname = '') {
		return self::update($t, $arr, array(
			$idname => $id
		), $low_priority, $dbname);
	}

	public static function insert_id() {
		$db = self::connect();
		return intval($db->insert_id());
	}

	public static function affected_rows($dbname = "") {
		$db = self::connect($dbname, false, false);
		return $db->affected_rows();
	}

	public static function table_exists($table, $dbname = "") {
		if (empty($table)) {
			return false;
		}
		list($db, $table) = pair($table, ".", "", $table);
		if (!empty($db)) {
			$db = " FROM $db";
		}
		$result = self::query_array("SHOW TABLES$db LIKE '$table'", null, null, null, $dbname);
		return (count($result) !== 0);
	}

	public static function table_columns($table, $dbname = "") {
		return self::query_array("DESC " . sql::quote_table($table), 'Field', null, null, $dbname);
	}

	public static function table_column_exists($table, $column, $dbname = "") {
		return array_key_exists($column, self::table_columns($table, $dbname));
	}

	public static function table_index_exists($table, $index, $dbname = "") {
		return array_key_exists($index, self::table_indexes($table, $dbname));
	}

	public static function table_indexes($table, $dbname = "") {
		$rows = self::query_array("SHOW INDEXES FROM " . sql::quote_table($table), null, null, array(), $dbname);
		$result = array();
		foreach ($rows as $row) {
			$key = $row['Key_name'];
			$result[$key][] = $row['Column_name'];
		}
		return $result;
	}

	public static function select_one_id($table, $id_name, $id, $dbname = "") {
		if (empty($id))
			return null;
		$where = array(
			$id_name => $id
		);
		return self::select_one_where($table, $where, false, $dbname);
	}

	public static function select_one_where($table, $where, $order_by = false, $dbname = "") {
		$sql = "SELECT * FROM " . sql::quote_table($table) . sql::where($where, "AND") . sql::order_by($order_by) . sql::limit(0, 1);
		return self::query_one($sql, false, false, $dbname);
	}

	public static function delete($table, $where, $dbname = "") {
		$sql = sql::delete($table, $where);
		self::query($sql, $dbname);
	}

	public static function repair_log($message) {
		error_log(gmdate('Y-m-d H:i:s') . ' ' . $message . "\n", 3, path(zesk::get('LOG_PATH'), 'repair.log'));
	}

	/**
	 *
	 * @return true if table is OK, false if table is !found || can !be repaired
	 */
	public static function repair_has_errors($x) {
		$result = false;
		$message = array();
		foreach ($x as $row) {
			$type = aevalue($row, "Msg_type");
			if ($type === "error") {
				$result = true;
				$message[] = 'Table=' . aevalue($row, 'Table', '-table') . ';Op=' . aevalue($row, 'Op', '-op-') . ';Msg_text=' . aevalue($row, 'Msg_text', '-no-message-');
			}
		}
		if (count($message)) {
			self::repair_log(implode("\n", $message));
		}
		return $result;
	}

	public static function get_repair_lock($table, $wait_seconds = 0) {
		return self::get_lock("repair_" . $table, $wait_seconds);
	}

	public static function is_being_repaired($table) {
		$x = self::get_repair_lock($table);
		if ($x) {
			// Acquired lock, not being repaired
			self::release_repair_lock($table);
			return false;
		}
		// Didn't acquire lock, is being repaired
		return true;
	}

	public static function release_repair_lock($table) {
		self::release_lock("repair_" . $table);
	}

	public static function get_lock($name, $wait_seconds = 0) {
		$wait_seconds = intval($wait_seconds);
		$name = sql::quote($name);
		$result = intval(self::query_one("SELECT GET_LOCK($name,$wait_seconds) AS X", "X", 0)) === 1;
		return $result;
	}

	public static function release_lock($name) {
		$name = sql::quote($name);
		$raw_result = self::query_one("SELECT RELEASE_LOCK($name) AS X", "X", null);
		$result = intval($raw_result);
		if ($result !== 1) {
			log::error("Released lock {name} FAILED (raw={raw_result})): ", array(
				"name" => $name,
				"backtrace" => debug_backtrace(false),
				"raw_result" => php::dump($raw_result)
			));
			return false;
		}
		return true;
	}

	public static function repair_if_necessary($table, $slow = false, $blocking = true) {
		if (!self::table_exists($table)) {
			return "table-not-found";
		}

		if (!self::get_repair_lock($table)) {
			if (!$blocking) {
				return "already";
			}
			while (self::get_repair_lock($table) === false) {
				sleep(1);
			}
			self::release_repair_lock($table);
			return true;
		}

		$result = self::query_array("CHECK TABLE $table" . ($slow ? "" : " QUICK"));
		$do_repair = self::repair_has_errors($result);

		if (!$do_repair) {
			zesk::set('repair_result', 'check_ok');
			self::release_repair_lock($table);
			return true;
		}

		self::repair_log("REPAIR TABLE $table FROM: " . _backtrace());

		$result = self::query_array("REPAIR NO_WRITE_TO_BINLOG TABLE $table" . ($slow ? "" : " QUICK"));
		$repair_errors = self::repair_has_errors($result);

		self::release_repair_lock($table);

		if (!$repair_errors) {
			zesk::set('repair_result', 'repaired');
			return true;
		}
		zesk::set('repair_result', 'repair_failed');
		return false;
	}

	public static function bytes_used($table, $default = null) {
		if (!self::table_exists($table))
			return $default;
		return self::query_one("SHOW TABLE STATUS LIKE '$table'", 'Data_length', $default);
	}

	/**
	 * Create a query
	 * @param string $db Databse to connect to
	 * @return Database_Query_Select
	 */
	public static function select($db = "") {
		return new Database_Query_Select(db::connect($db));
	}
}
