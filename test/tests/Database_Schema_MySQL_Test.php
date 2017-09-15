<?php

/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Schema_MySQL_Test extends Test_Unit {
	/**
	 *
	 * @var array
	 */
	protected $load_modules = array(
		"MySQL"
	);

	/**
	 *
	 * @return mysql\Database
	 */
	function db() {
		$testx = $this->application->database_factory();

		$this->assert($testx->type() === "mysql");
		return $testx;
	}
	function test_datetime_timestamp() {
		$sql0 = "CREATE TABLE test ( id integer unsigned NOT NULL, created datetime NULL ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
		$sql1 = "CREATE TABLE test ( id integer unsigned NOT NULL, created timestamp NULL ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";

		$db = $this->application->database_factory();
		$table0 = $db->parse_create_table($sql0, __METHOD__);
		$table1 = $db->parse_create_table($sql1, __METHOD__);

		// 		$this->log(JSON::encode($table0->option()));
		// 		$this->log(JSON::encode($table1->option()));

		$result = Database_Schema::update($db, $table0, $table1, false);

		$datatype = $db->data_type();

		$this->assert_false($datatype->native_types_equal("timestamp", "datetime"));
		$this->assert_true($datatype->native_types_equal("int", "integer(12)"));

		$this->assert_arrays_equal($result, array(
			"ALTER TABLE `test` CHANGE COLUMN `created` `created` timestamp NULL DEFAULT NULL"
		));
	}
	function test_primary_key_location() {
		$sql['base'] = "CREATE TABLE `{table}` (
			`id` integer unsigned NOT NULL,
			`name` varchar(255) NOT NULL DEFAULT ''
		);";
		$sql['separate'] = "CREATE TABLE `{table}` (
			`id` integer unsigned NOT NULL AUTO_INCREMENT,
			`name` varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY (`id`)
		);";
		$sql['together'] = "CREATE TABLE `{table}` (
			`id` integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`name` varchar(255) NOT NULL DEFAULT '',
		);";

		$table = __FUNCTION__;
		$sql = map($sql, compact("table"));
		$db = $this->application->database_factory();

		$result = $db->query("DROP TABLE IF EXISTS $table");
		$this->assert_equal($result, true);

		$this->assert_true(!$db->table_exists($table), "$table should not exist");

		foreach ($sql as $key => $create) {
			$result = Database_Schema::table_synchronize($db, $create, false);
			$db->query($result);
		}
	}
	function test_0() {
		$table = 'temp_test_SearchPhrase';

		$sql = "CREATE TABLE `$table` (
		`ID` int(11) unsigned NOT NULL auto_increment,
		`Hash` varchar(32) default NULL,
		`Phrase` text,
		`Created` datetime NULL,
		`Modified` DATETIME NULL,
		`Status` smallint(1) DEFAULT '0',
		`IsOrganic` tinyint DEFAULT '0',
		`LastUsed` DATETIME NULL,
		`UseCount` int(11) DEFAULT '1',
		PRIMARY KEY  (`ID`),
		UNIQUE `Hash` (`Hash`),
		KEY `created` ( `Created` ),
		KEY `phrase` (`Phrase`(64))
		);";

		Database_Schema::debug(true);

		/* @var $db Database */
		$db = $this->application->database_factory();
		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$this->assert($db->connect(), "connecting to " . $db->safe_url());

		$this->assert($db->table_exists($table), "$db->table_exists($table)");

		$object_table = $db->parse_create_table($sql, __METHOD__);
		$table_name = $object_table->name();

		$this->assert_equal($table_name, $table);

		$db_table = $db->database_table($table_name);

		$result = Database_Schema::update($db, $db_table, $object_table);

		$this->assert_equal($result, array());
	}
	function test_1() {
		$table = 'temp_test_keywords';

		$sql = "CREATE TABLE `$table` (
		ID int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
		Hash varchar(32) NOT NULL,
		Phrase text,
		Created datetime NOT NULL,
		Modified DATETIME NOT NULL,
		Status smallint(1) DEFAULT '0',
		IsOrganic enum('false','true') DEFAULT 'false',
		LastUsed DATETIME NOT NULL,
		UNIQUE Hash (Hash),
		INDEX created ( Created ),
		INDEX phrase ( Phrase(64) )
		);";

		Database_Schema::debug(true);

		$db = $this->application->database_factory();

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$this->assert($db->connect(), "connecting to " . $db->safe_url());

		$this->assert($db->table_exists($table));

		$object_table = $db->parse_create_table($sql, __METHOD__);
		$table_name = $object_table->name();

		$this->assert("'$table_name' === '$table'");

		$db_table = $db->database_table($table_name);

		$result = Database_Schema::update($db, $db_table, $object_table);

		$this->assert($result === array(), PHP::dump($result) . ' === array()');

		echo basename(__FILE__) . ": Success.\n";
	}
	function test_2() {
		$table = 'temp_test_actions';

		$sql = "CREATE TABLE `$table` (
		`ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
		`ActionTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`PageView` int(11) unsigned DEFAULT NULL,
		`Session` int(11) unsigned NOT NULL DEFAULT '0',
		`ActionType` int(11) unsigned NOT NULL DEFAULT '0',
		`Elapsed` int(11) DEFAULT NULL,
		`RealValue0` decimal(10,2) DEFAULT '0.00',
		`RealValue1` decimal(10,2) DEFAULT '0.00',
		`RealValue2` decimal(10,2) DEFAULT '0.00',
		`RealValue3` decimal(10,2) DEFAULT '0.00',
		`DataValue0` varchar(64) DEFAULT NULL,
		`DataValue1` varchar(128) DEFAULT NULL,
		`DataValue2` varchar(128) DEFAULT NULL,
		`DataValue3` varchar(128) DEFAULT NULL,
		PRIMARY KEY (`ID`),
		KEY `sess` (`Session`),
		KEY `time` (`ActionTime`)
		) AUTO_INCREMENT=1426";

		Database_Schema::debug(true);

		$db = $this->application->database_factory();

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$this->assert($db->connect(), "connecting to " . $db->safe_url());

		$this->assert($db->table_exists($table));

		$object_table = $db->parse_create_table($sql, __METHOD__);
		$table_name = $object_table->name();

		$this->assert("'$table_name' === '$table'");

		$db_table = $db->database_table($table_name);

		$result = Database_Schema::update($db, $db_table, $object_table);

		$this->assert($result === array(), PHP::dump($result) . ' === array()');
	}
	function test_3() {
		$table = 'temp_test_varbin';

		$sql = "CREATE TABLE `$table` (
		`ID` int(11) unsigned NOT NULL auto_increment,
		`Depth` tinyint(4) default '0',
		`CodeName` varbinary(80) NOT NULL default '',
		`Name` varchar(128) NOT NULL default '',
		PRIMARY KEY  (`ID`),
		UNIQUE KEY `codename` (`Depth`,`CodeName`)
		);
		";

		Database_Schema::debug(true);

		$db = $this->application->database_factory();

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$this->assert($db->connect(), "connecting to " . $db->safe_url());

		$this->assert($db->table_exists($table));

		$object_table = $db->parse_create_table($sql, __METHOD__);
		$table_name = $object_table->name();

		$this->assert("'$table_name' === '$table'");

		$db_table = $db->database_table($table_name);

		$result = Database_Schema::update($db, $db_table, $object_table);

		$this->assert($result === array(), PHP::dump($result) . ' === array()');

		$db->query("DROP TABLE IF EXISTS $table");

		$this->log(__FUNCTION__);
	}
	function test_4() {
		DBSchemaTest4::$test_table = $table = 'temp_test_multi_create';

		DBSchemaTest4::$test_table2 = $table2 = 'temp_test_multi_create2';

		$db = $this->application->database_factory();

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query("DROP TABLE IF EXISTS $table2");

		$object = new DBSchemaTest4($this->application);

		$result = Database_Schema::update_object($object);
		$this->log($result);
		$db->query($result);

		$this->assert($db->table_exists($table));
		$this->assert($db->table_exists($table2));

		$n_rows = $db->query_one("SELECT COUNT(*) AS X FROM $table", "X", null);
		$this->assert(intval($n_rows) === 1, "intval($n_rows) === 1");

		$db->query("DROP TABLE IF EXISTS $table2");

		$this->assert(!$db->table_exists($table2));

		$object = new DBSchemaTest4($this->application);
		$result = Database_Schema::update_object($object);
		$db->query($result);

		$this->assert($db->table_exists($table));
		$this->assert($db->table_exists($table2));

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM $table", "X", null)) === 1);

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query("DROP TABLE IF EXISTS $table2");

		echo basename(__FILE__) . ": Success.\n";
	}
	function test_5() {
		$db = $this->application->database_factory();

		DBSchemaTest5::$test_table = $table = 'keywords_test';
		$db->query("DROP TABLE IF EXISTS $table");

		$object = new DBSchemaTest5($this->application);
		$result = Database_Schema::update_object($object);
		$db->query($result);

		$this->assert($db->table_exists($table));

		Database_Schema::debug(true);

		$object = new DBSchemaTest5($this->application);
		$result = Database_Schema::update_object($object);
		$this->assert($result === array(), gettype($result));

		//$db->query("DROP TABLE IF EXISTS $table");

		echo basename(__FILE__) . ": Success.\n";
	}
	function test_6() {
		$table = 'keywords_test';

		$db = $this->application->database_factory();

		$db->query("DROP TABLE IF EXISTS $table");

		DBSchemaTest6::$test_table = $table;
		$object = new DBSchemaTest6($this->application);
		$result = Database_Schema::update_object($object);
		$db->query($result);

		$this->assert($db->table_exists($table));

		Database_Schema::debug(true);

		DBSchemaTest7::$test_table = $table;
		$object = new DBSchemaTest7($this->application);
		$result = Database_Schema::update_object($object);

		$check_result = array(
			"ALTER TABLE `keywords_test` ADD COLUMN `Proto` tinyint NOT NULL DEFAULT 0 AFTER `Protocol`"
		);

		$this->assert_arrays_equal($result, $check_result, true);

		//$db->query("DROP TABLE IF EXISTS $table");

		echo basename(__FILE__) . ": Success.\n";
	}
	function test_8() {
		$table = 'bigint_test';

		$db = $this->application->database_factory();

		$db->query("DROP TABLE IF EXISTS $table");

		DBSchemaTest8::$test_table = $table;
		$object = new DBSchemaTest8($this->application);
		$result = Database_Schema::update_object($object);
		$db->query($result);

		$this->assert($db->table_exists($table));

		Database_Schema::debug(true);

		$object = new DBSchemaTest8($this->application);
		$result = Database_Schema::update_object($object);

		$this->assert_arrays_equal($result, array());

		$db->query("DROP TABLE IF EXISTS $table");

		echo basename(__FILE__) . ": Success.\n";
	}
}
class Class_DBSchemaTest4 extends Class_Object {
	public function initialize() {
		parent::initialize();
		$this->table = DBSchemaTest4::$test_table;
	}
	public $column_types = array(
		"ID" => self::type_id,
		"Depth" => self::type_integer,
		"CodeName" => self::type_string,
		"Name" => self::type_string
	);
}
class DBSchemaTest4 extends Object {
	public static $test_table = "";
	public static $test_table2 = "";
	function schema() {
		$table = self::$test_table;
		$table2 = self::$test_table2;
		return "CREATE TABLE `$table` (
		`ID` int(11) unsigned NOT NULL auto_increment,
		`Depth` tinyint(4) default '0',
		`CodeName` varbinary(80) NOT NULL default '',
		`Name` varchar(128) NOT NULL default '',
		PRIMARY KEY  (`ID`),
		UNIQUE KEY `codename` (`Depth`,`CodeName`)
		);

		CREATE TABLE `$table2` (
		`ID` int(11) unsigned NOT NULL auto_increment,
		`Depth` tinyint(4) default '0',
		`CodeName` varbinary(80) NOT NULL default '',
		`Name` varchar(128) NOT NULL default '',
		PRIMARY KEY  (`ID`),
		UNIQUE KEY `codename` (`Depth`,`CodeName`)
		);
		INSERT INTO $table ( Depth, CodeName, Name ) VALUES ( 3, 'foo', 'bar');";
	}
}
class Class_DBSchemaTest5 extends Class_Object {
	public function initialize() {
		parent::initialize();
		$this->table = DBSchemaTest5::$test_table;
	}
	public $column_types = array(
		"ID" => self::type_id,
		"Hash" => self::type_string,
		"Phrase" => self::type_string,
		"Created" => self::type_created,
		"Modified" => self::type_modified,
		"Status" => self::type_integer,
		"IsOrganic" => self::type_string,
		"LastUsed" => self::type_timestamp
	);
}
class DBSchemaTest5 extends Object {
	public static $test_table = null;
	function schema() {
		return "CREATE TABLE `{table}` (
		`ID` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
		`Hash` binary(16) NOT NULL,
		`Phrase` varchar(255) NOT NULL,
		`Created` timestamp NOT NULL DEFAULT 0,
		`Modified` timestamp NOT NULL DEFAULT 0,
		`Status` smallint(1) DEFAULT '0',
		`IsOrganic` enum('false','true') DEFAULT 'false',
		`LastUsed` timestamp NOT NULL DEFAULT 0,
		UNIQUE Hash (Hash) USING HASH,
		INDEX created ( Created ),
		INDEX phrase ( Phrase(64) )
		);";
	}
}
class Class_DBSchemaTest6 extends Class_Object {
	public $column_types = array(
		"ID" => self::type_id,
		"Hash" => self::type_string,
		"Protocol" => self::type_string,
		"Proto" => self::type_object,
		"Domain" => self::type_object,
		"Port" => self::type_integer,
		"URI" => self::type_object,
		"QueryString" => self::type_object,
		"Title" => self::type_object,
		"Fragment" => self::type_string,
		"Frag" => self::type_object
	);
	public function initialize() {
		parent::initialize();
		$this->table = DBSchemaTest6::$test_table;
	}
}
class DBSchemaTest6 extends Object {
	public static $test_table = null;
	function schema() {
		return "CREATE TABLE `{table}` (
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
		);";
	}
}
class Class_DBSchemaTest7 extends Class_Object {
	public $column_types = array(
		"ID" => self::type_id,
		"Hash" => self::type_string,
		"Protocol" => self::type_string,
		"Proto" => self::type_object,
		"Domain" => self::type_object,
		"Port" => self::type_integer,
		"URI" => self::type_object,
		"QueryString" => self::type_object,
		"Title" => self::type_object,
		"Fragment" => self::type_string,
		"Frag" => self::type_object
	);
	public function initialize() {
		parent::initialize();
		$this->table = DBSchemaTest7::$test_table;
	}
}
class DBSchemaTest7 extends Object {
	public static $test_table = null;
	function schema() {
		return "CREATE TABLE `{table}` (
	`ID` int(11) unsigned NOT NULL auto_increment,
	`Hash` char(32) NOT NULL,
	`Protocol` varchar(7) NOT NULL default '',
	`Proto` tinyint NOT NULL default '0',
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
	);";
	}
}
class Class_DBSchemaTest8 extends Class_Object {
	public $column_types = array(
		"ID" => self::type_id,
		"Hash" => self::type_string,
		"Size" => self::type_integer
	);
	public function initialize() {
		parent::initialize();
		$this->table = DBSchemaTest8::$test_table;
	}
}
class DBSchemaTest8 extends Object {
	public static $test_table = null;
	function schema() {
		return "CREATE TABLE `{table}` (
			`ID` int(11) unsigned NOT NULL auto_increment,
			`Hash` char(32) NOT NULL,
			`Size` bigint unsigned NOT NULL,
			PRIMARY KEY (ID)
		);";
	}
}

