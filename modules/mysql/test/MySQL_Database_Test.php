<?php
namespace zesk;

class MySQL_Database_Test extends Test_Unit {
	protected $load_modules = array(
		"MySQL"
	);
	function test_types_compatible() {
		$mysql = $this->application->database_factory("mysql://root@localhost/mysql", array(
			'connect' => false
		));
		/* @var $mysql Database_MySQL */
		$this->assert_true(true);
	}
	
	/**
	 *
	 * @return \mysql\Database
	 */
	function database() {
		$db = $this->application->database_factory();
		
		$this->assert_in_array(array(
			"mysql",
			"mysqli"
		), $db->type(), "Type must be mysqli or mysql");
		return $db;
	}
	function test_mysql_1() {
		$db = $this->database();
		
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
		
		$this->assert_instanceof($table, "zesk\\Database_Table");
		
		echo "Test created because preg_match dies on web2 with above input... due to pcre backtracking stack overflow ... or something like that\n";
	}
	function test_mysql_funcs_1() {
		$db = $this->database();
		
		$test_table = $this->test_table('test_table');
		
		$db->database_name();
		
		$filename = path($this->test_sandbox("dump.sql"));
		$options = array();
		$db->connect();
		$db->dump($filename, $options);
		
		$db->disconnect();
		$this->assert_equal($db->connected(), false);
		$success = false;
		try {
			/**
			 * Set auto_connect to false
			 */
			$db->query("SHOW TABLES", array(
				"auto_connect" => false
			));
		} catch (Database_Exception $e) {
			$this->assert_contains($e->getMessage(), "Not connected");
			$success = true;
		}
		$this->assert_equal($db->connected(), false);
		$this->assert($success);
		
		$db->connect();
		
		if ($db->can("create database")) {
			$url = null;
			//$db->createDatabase('mysql://test_user:test_pass@localhost/zesk_create_test_db');
		}
		
		$db->tables_case_sensitive();
		
		$this->assert($db->can(Database::feature_list_tables) === true);
		$this->assert($db->can(Database::feature_create_database) === true);
		
		$tables = $db->list_tables();
		
		$debug = false;
		
		foreach ($tables as $table) {
			if ($debug) {
				$this->log("Showing table {table}", array(
					"table" => $table
				));
			}
			$sql = $db->query_one("SHOW CREATE TABLE $table", 1);
			if ($debug) {
				$this->log("Showing table {table} = {sql}", array(
					"table" => $table,
					"sql" => $sql
				));
			}
			$this->assert_string_begins($sql, "CREATE TABLE");
			$this->assert(strpos($sql, "$table") !== false);
			
			$dbTableObject = $db->parse_create_table($sql);
			$sql = $db->sql()->create_table($dbTableObject);
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
			
			$result = $db->table_information($table);
		}
		
		$table = null;
		
		$success = false;
		try {
			$table = null;
			$db->database_table($table);
		} catch (Database_Exception $e) {
			$success = true;
		}
		$this->assert($success === true);
		
		$table = null;
		$type = null;
		$db->sql()->alter_table_type($table, $type);
		
		$success = false;
		try {
			$table = new Database_Table($db, "Foo");
			$index = new Database_Index($table);
			$index->type("DUCKY");
			$this->assert_equal($index->type(), Database_Index::Index);
			$sql = $db->sql()->alter_table_index_drop($table, $index);
			$this->log($sql);
		} catch (Exception_Semantics $e) {
			$success = true;
		}
		$this->assert($success);
		
		$table = new Database_Table($db, "Foo");
		$index = new Database_Index($table, "dude");
		$sql = $db->sql()->alter_table_index_drop($table, $index);
		$this->assert_equal($sql, "ALTER TABLE `Foo` DROP INDEX `dude`");
		
		$table = new Database_Table($db, "Foo");
		$index = new Database_Index($table, "dude");
		$index->type(Database_Index::Primary);
		
		$sql = $db->sql()->alter_table_index_drop($table, $index);
		$this->assert_equal($sql, "ALTER TABLE `Foo` DROP PRIMARY KEY");
		
		$table = null;
		$name = null;
		$indexes = array(
			"Foo" => 32
		);
		$db->sql()->index_type($table, $name, Database_Index::Index, $indexes);
		$table = null;
		$name = null;
		$db->sql()->index_type($table, $name, Database_Index::Unique, $indexes);
		$table = null;
		$name = null;
		$db->sql()->index_type($table, $name, Database_Index::Primary, $indexes);
		
		$table = null;
		$name = null;
		$indexType = null;
		$indexes = array(
			"Foo" => 32
		);
		
		$table = new Database_Table($db, "Foo");
		$table->column_add(new Database_Column($table, "ID", array(
			"sql_type" => "integer unsigned"
		)));
		$table->column_add(new Database_Column($table, "Name", array(
			"sql_type" => "varchar(32)"
		)));
		$index = new Database_Index($table, "dude");
		$index->column_add("ID");
		$index->type(Database_Index::Primary);
		
		$sql = $db->sql()->alter_table_index_add($table, $index);
		$this->assert_equal($sql, "ALTER TABLE `Foo` ADD PRIMARY KEY (`ID`)");
		
		$table = new Database_Table($db, $table_name = "TestLine_" . __LINE__);
		$dbColOld = new Database_Column($table, "Foo");
		$dbColOld->sql_type("varchar(32)");
		$dbColNew = new Database_Column($table, "Foo");
		$dbColNew->sql_type("varchar(33)");
		$sql = $db->sql()->alter_table_change_column($table, $dbColOld, $dbColNew);
		$this->assert_equal($sql, "ALTER TABLE `$table_name` CHANGE COLUMN `Foo` `Foo` varchar(33) NULL");
		
		$query = null;
		$result = $db->query($query);
		$this->assert_null($result);
		
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
		
		$db->now();
		
		$db->now_utc();
		
		$tables = $db->list_tables();
		$this->assert(count($tables) > 0, "Test database should contain at least one table");
		
		foreach ($tables as $table) {
			$this->assert_true($db->table_exists($table), "$table returned by list_tables but does not exist?");
		}
		
		$word = null;
		$db->is_reserved_word($word);
		
		$sql = "CREATE TABLE Foo ( ID integer )";
		$db->parse_create_table($sql);
		
		$db = $this->application->database_factory();
		
		$url = $db->url();
		
		$this->assert(!empty($url));
		
		$filler = "ANTIDISESTABLISHMENTARIANISM";
		$safe_url = $db->safe_url($filler);
		$this->assert(strpos($safe_url, $filler) !== false, "Safe URL $safe_url does not contain $filler");
		
		$table = new Database_Table($db, $table_name = "TestTable" . __LINE__);
		$column = new Database_Column($table, "hello");
		$column->sql_type("varchar(2)");
		$sqlType = null;
		$after_col = false;
		$sql = $db->sql()->alter_table_column_add($table, $column);
		$this->assert_equal($sql, "ALTER TABLE `$table_name` ADD COLUMN `hello` varchar(2) NULL");
		
		$table = new Database_Table($db, $table_name = "TestTable" . __LINE__);
		$column = new Database_Column($table, "hello");
		$column->sql_type("varchar(2)");
		
		$sql = $db->sql()->alter_table_column_drop($table, $column);
		$this->assert_equal($sql, "ALTER TABLE `$table_name` DROP COLUMN `hello`");
		
		$col = "Hippy";
		$alias = 'Dippy';
		$sql = $db->sql()->column_alias($col, $alias);
		$this->assert_equal($sql, "`Dippy`.`Hippy`");
		
		$db->transaction_start();
		
		$success = true;
		$db->transaction_end($success);
		
		$table = null;
		$type = false;
		$db->new_database_table($table, $type);
		
		$this->assert_is_string($db->table_prefix());
	}
	public function test_estimate_rows() {
		$db = $this->database();
		$this->assert_true($db->table_exists("test_table"));
		$sql = "SELECT * FROM test_table";
		$db->estimate_rows($sql);
	}
}

