<?php
class Database_MySQL_Test extends Test_Unit {

	function test_types_compatible() {
		$mysql = Database::factory("mysql://root@localhost/mysql", array(
			'connect' => false
		));
		/* @var $mysql Database_MySQL */
		$this->assert_true(true);
	}

	/**
	 *
	 * @return Database_MySQL
	 */
	function db() {
		$url = zesk::get('DB_URL');
		$this->assert(begins($url, 'mysql:'));
		/* @var $db Database_MySQL */

		$db = Database::factory($url);

		$this->assert($db->type() === "mysql");
		return $db;
	}
	function test_mysql_1() {
		$db = $this->db();

		$sql = <<<EOF
CREATE TABLE `tracking_1999` (
        `id` int(11) unsigned NOT NULL auto_increment,
        `utc` timestamp NOT NULL DEFAULT 0,
        `cookieid` char(32) NOT NULL,
        `sess_id` int(11) unsigned NOT NULL,
        `crcat` varchar(80) default NULL,
        `crcpn` varchar(80) default NULL,
        `crseg` varchar(80) default NULL,
        `landing_id` int(11) unsigned default NULL,
        `userip` varchar(15) default NULL,
        `inner_ip` int(11) unsigned default NULL,
        `ip` int(11) unsigned NOT NULL,
        `client_time` timestamp NULL DEFAULT 0,
        `gmt_offset` smallint(6) NULL,
        `ref_id` int(11) unsigned NULL,
        `page_id` int(11) unsigned default NULL,
        `top_page_id` int(11) unsigned default NULL,
        `ua_id` int(11) unsigned default NULL,
        `nojs` tinyint NOT NULL DEFAULT 'false',
        `nocook` tinyint NOT NULL DEFAULT 'false',
        `action_code` tinyint(1) default NULL,
        `action_id` int(11) unsigned default NULL,
        `action_val1` double(6,2) default NULL,
        `action_reference1` varchar(100) default NULL,
        `action_val2` double(6,2) default NULL,
        `action_reference2` varchar(100) default NULL,
        PRIMARY KEY  (`id`),
        KEY `sess_id` (`sess_id`),
        KEY `landing_id` (`landing_id`),
        KEY `ip` (`ip`),
        KEY `cookieid` (`cookieid`),
        KEY `actions` (`action_id`),
        KEY `utc_action_id` (`utc`,`action_id`),
        KEY `action_utc` (`action_code`,`utc`),
        KEY `utc_ts` (`utc`)
);
EOF;

		$table = $db->parse_create_table($sql);

		$this->assert($table instanceof Database_Table);

		echo "Test created because preg_match dies on web2 with above input... due to pcre backtracking stack overflow ... or something like that\n";
	}
	function test_mysql_funcs_1() {
		$db = $this->db();

		$test_table = $this->test_table('test_table');

		$db->database_name();

		$where = null;
		$suffix = null;
		$inList = null;
		$exList = null;
		$lock_first = true;
		$db->connect();
		$db->dump($this->test_sandbox(), date('Y-m-d'), $inList, $exList, $lock_first);

		$db->disconnect();

		$success = false;
		try {
			$db->query("SHOW TABLES");
		} catch (Database_Exception $e) {
			$this->assert(strpos($e->getMessage(), "Not connected") !== false);
			$success = true;
		}
		$this->assert($success);

		$db->connect();

		if ($db->can("create database")) {
			$url = null;
			//$db->createDatabase('mysql://test_user:test_pass@localhost/zesk_create_test_db');
		}

		$db->tablesCaseSensitive();

		$this->assert($db->can(Database::feature_list_tables) === true);
		$this->assert($db->can(Database::feature_create_database) === true);

		$tables = $db->list_tables();

		foreach ($tables as $table) {
			$sql = $db->sql_get_create_table($table);
			$this->assert(str::begins($sql, "CREATE TABLE"));
			$this->assert(strpos($sql, "$table") !== false);

			$dbTableObject = $db->parse_create_table($sql);
			$sql = $db->sql_create_table($dbTableObject);
			if (!is_array($sql)) {
				$sqls = array(
					$sql
				);
			} else {
				$sqls = $sql;
			}
			foreach ($sqls as $sql) {
				$this->assert(str::begins($sql, "CREATE TABLE"));
				$this->assert(strpos($sql, "$table") !== false);
			}

			$result = $db->tableInformation($table);
		}

		$type = null;
		$default_value = null;
		$db->sql_type_default($type, $default_value);

		$table = null;

		$success = false;
		try {
			$table = null;
			$db->databaseTable($table);
		} catch (Database_Exception_MySQL $e) {
			$success = true;
		}
		$this->assert($success === true);

		$table = null;
		$newType = null;
		$db->sql_alter_table_type($table, $newType);

		$success = false;
		try {
			$table = null;
			$name = null;
			$indexType = null;
			$db->sql_alter_table_index_drop($table, $name, $indexType);
		} catch (Exception $e) {
			$success = true;
		}
		$this->assert($success);

		$table = null;
		$name = null;
		$indexType = null;
		$db->sql_alter_table_index_drop($table, $name, Database_Index::Primary);
		$table = null;
		$name = null;
		$indexType = null;
		$db->sql_alter_table_index_drop($table, $name, Database_Index::Index);
		$db->sql_alter_table_index_drop($table, $name, Database_Index::Unique);

		$table = null;
		$name = null;
		$indexes = array(
			"Foo" => 32
		);
		$db->sql_index_type($table, $name, Database_Index::Index, $indexes);
		$table = null;
		$name = null;
		$db->sql_index_type($table, $name, Database_Index::Unique, $indexes);
		$table = null;
		$name = null;
		$db->sql_index_type($table, $name, Database_Index::Primary, $indexes);

		$table = null;
		$name = null;
		$indexType = null;
		$indexes = array(
			"Foo" => 32
		);
		$db->sql_alter_table_index_add($table, $name, Database_Index::Primary, $indexes);

		$table = null;
		$dbColOld = new Database_Column("Foo", "varchar(32)");
		$dbColNew = new Database_Column("Foo", "varchar(33)");
		$db->sql_alter_table_change_column($table, $dbColOld, $dbColNew);

		$offset = -1;
		$limit = -1;
		$db->_MySQL_Limit($offset, $limit);

		$success = false;
		try {
			$query = null;
			$db->query($query);
		} catch (Database_Exception_MySQL $e) {
			$success = true;
		}
		$this->assert($success);

		$db->query("SHOW TABLES");

		$result = null;
		$db->affected_rows($result);

		$result = null;
		$db->free($result);

		$db->insert_id();

		$sql = array();
		$db->mixed_query($sql);

		$sql = "SHOW TABLES";
		$k = null;
		$v = null;
		$default = null;
		$db->query_array($sql, $k, $v, $default);

		$db->sql_now();

		$db->sql_now_utc();

		$value = null;
		$db->sql_validate_datetime($value);

		$value = null;
		$db->sql_validate_Date($value);

		$value = null;
		$db->sql_validate_time($value);

		$value = null;
		$db->sql_validate_timeStamp($value);

		$value = null;
		$db->sql_parse_datetime($value);

		$value = null;
		$db->sql_parse_date($value);

		$value = null;
		$db->sql_parse_time($value);

		$table = null;
		$db->table_exists($table);

		$sql = null;
		$db->sql_format_datetime($sql);

		$sql = null;
		$db->sqlToDate($sql);

		$value = null;
		$db->sql_parse_timeStamp($value);

		$sql = null;
		$db->sql_format_timestamp($sql);

		$sqlDate = null;
		$number = null;
		$units = "second";
		$db->sql_function_date_add($sqlDate, $number, $units);

		$sqlDate = null;
		$number = null;
		$units = "second";
		$db->sql_function_date_subtract($sqlDate, $number, $units);

		$table = null;
		$name = '';
		$db->sql_table_as($table, $name);

		$word = null;
		$db->is_reserved_word($word);

		$column_name = null;
		$cmp = null;
		$string = null;
		$db->sql_function_compare_binary($column_name, $cmp, $string);

		$sql = null;
		$db->sql_format_string($sql);

		$sqlType = null;
		$size = null;
		Database_MySQL::parseType($sqlType, $size);

		$t = null;
		Database_MySQL::_basicType($t);

		$sqlType = null;
		Database_MySQL::sqlBasicType($sqlType);

		$sqlType0 = null;
		$sqlType1 = null;
		$db->types_compatible($sqlType0, $sqlType1);

		$value = null;
		$db->sql_parse_boolean($value);

		$sql = null;
		$db->sql_format_boolean($sql);

		$value = null;
		$db->sql_format_password($value);

		$func = null;
		$memberName = null;
		$alias = '';
		$db->sql_format_function($func, $memberName, $alias);

		$db->hasInnoDB();

		$sql = "SELECT * FROM test_table";
		$db->estimate_rows($sql);

		$sql = "CREATE TABLE Foo ( ID integer )";
		$db->parse_create_table($sql);

		$url = zesk::get('DB_URL');
		Database_MySQL::factory($url);

		$url = $db->url();

		$this->assert(!empty($url));

		$filler = "ANTIDISESTABLISHMENTARIANISM";
		$safe_url = $db->safe_url($filler);
		$this->assert(strpos($safe_url, $filler) !== false, "Safe URL $safe_url does not contain $filler");

		$table = null;
		$column = new Database_Column("hello", "varchar(2)");
		$sqlType = null;
		$after_col = false;
		$db->sql_alter_table_column_add($table, $column, $after_col);

		$table = null;
		$dbColName = null;
		$db->sql_alter_table_column_drop($table, $dbColName);

		$col = null;
		$alias = '';
		$db->sql_column_alias($col, $alias);

		$db->transaction_start();

		$success = true;
		$db->transaction_end($success);

		$table = null;
		$type = false;
		$db->new_database_table($table, $type);

		$db->table_prefix();

		$db->__sleep();

		$lkeys = null;
		$rkeys = null;
		$db->walk($lkeys, $rkeys);

		$db->option();

		$remove = false;
		$db->options_exclude($remove);

		$selected = false;
		$db->options_include($selected);

		$db->option_keys();

		$name = null;
		$checkEmpty = false;
		$db->has_option($name, $checkEmpty);

		$mixed = null;
		$value = false;
		$overwrite = true;
		$db->set_option($mixed, $value, $overwrite);
	}
	function test_schema0() {
		$db_url = zesk::get('DB_URL');

		$updates = Database_Schema::updateObject(Object::factory('DBSchemaTest_columns_0'));
		dump($updates);
		//TODO - not sure what this is testing but perhaps the SQL caused errors previously?
		$updates = Database_Schema::updateObject(Object::factory('DBSchemaTest_columns_1'));
		dump($updates);
		//TODO - not sure what this is testing but perhaps the SQL caused errors previously?
	}
}
class DBSchemaTest_columns_0 extends Object {
	protected $columns = array(
		"ID",
		"Hash",
		"Protocol",
		"Domain",
		"Port",
		"URI",
		"QueryString"
	);
	function schema() {
		return map("CREATE TABLE `{table}` (
				`ID` int(11) unsigned NOT NULL auto_increment,
				`Hash` char(32) NOT NULL,
				`Protocol` varchar(7) NOT NULL default '',
				`Domain` int(11) unsigned default NULL,
				`Port` smallint(11) unsigned NULL,
				`URI` int(11) unsigned default NULL,
				`QueryString` int(11) unsigned default NULL,
				`Title` int(11) unsigned NULL,
				`Fragment` text,
				`Frag` int(11) unsigned NULL,
				PRIMARY KEY  (`ID`),
				UNIQUE KEY `Hash` (`Hash`) USING HASH,
				KEY `domain` (`Domain`),
				KEY `title` (`Title`)
			);", array(
			"table" => get_class($this)
		));
	}
}
class DBSchemaTest_columns_1 extends Object {
	protected $columns = array(
		"ID",
		"Hash",
		"Protocol",
		"Domain",
		"Port",
		"URI",
		"QueryString"
	);
	function schema() {
		return map("CREATE TABLE `{table}` (
				`ID` int(11) unsigned NOT NULL auto_increment,
				`Hash` char(32) NOT NULL,
				`Protocol` varchar(7) NOT NULL default '',
				`Domain` int(11) unsigned default NULL,
				`Port` smallint(11) unsigned NULL,
				`URI` int(11) unsigned default NULL
			);", array(
			"table" => get_class($this)
		));
	}
}

