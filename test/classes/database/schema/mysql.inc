<?php
class Test_Database_Schema_MySQL extends Test_Unit {

	/**
	 *
	 * @return Database_MySQL
	 */
	function db() {
		$url = zesk::get('DB_URL');
		$this->assert(begins($url, 'mysql:'), "URL $url does not begin with mysql:");
		/* @var $testx Database_MySQL */

		$testx = Database::factory($url);

		$this->assert($testx->type() === "mysql");
		return $testx;
	}

	function test_datetime_timestamp() {
		$sql0 = "CREATE TABLE test ( id integer unsigned NOT NULL, created datetime NULL ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
		$sql1 = "CREATE TABLE test ( id integer unsigned NOT NULL, created timestamp NULL ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";

		$db = Database::instance();
		$table0 = $db->parse_create_table($sql0);
		$table1 = $db->parse_create_table($sql1);

		$result = Database_Schema::update($db, $table0, $table1, false);

		$datatype = $db->data_type();

		$this->assert_false($datatype->native_types_equal("timestamp", "datetime"));
		$this->assert_true($datatype->native_types_equal("int", "integer(12)"));

		$this->assert_arrays_equal($result, array(
			"ALTER IGNORE TABLE `table` CHANGE COLUMN `created` `created` timestamp NULL"
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
		$this->test_table($table);

		$db = Database::factory();
		foreach ($sql as $key => $create) {
			$result = Database_Schema::table_synchronize($db, $create, false);
			$db->query($result);
		}

	}
	function test_0() {
		$db_url = zesk::get('DB_URL');

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

		$db = Database::factory($db_url);

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$this->assert($db->connect(), "connecting to " . url::remove_password($db_url));

		$this->assert($db->table_exists($table), "$db->table_exists($table)");

		$object_table = $db->parse_create_table($sql);
		$table_name = $object_table->name();

		$this->assert_equal($table_name, $table);

		$db_table = $db->database_table($table_name);

		$result = Database_Schema::update($db, $db_table, $object_table);

		$this->assert_equal($result, array());
	}

	function test_1() {
		$db_url = zesk::get('DB_URL');

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

		log::level(log::ERROR);
		Database_Schema::debug(true);

		$db = Database::factory($db_url);

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$this->assert($db->connect(), "connecting to " . url::remove_password($db_url));

		$this->assert($db->table_exists($table));

		$object_table = $db->parse_create_table($sql);
		$table_name = $object_table->name();

		$this->assert("'$table_name' === '$table'");

		$db_table = $db->database_table($table_name);

		$result = Database_Schema::update($db, $db_table, $object_table);

		$this->assert($result === array(), php::dump($result) . ' === array()');

		echo basename(__FILE__) . ": Success.\n";
	}

	function test_2() {
		$db_url = zesk::get('DB_URL');

		$table = 'temp_test_actions';

		$sql = "CREATE TABLE `$table` (
		`ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
		`ActionTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
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

		log::level(log::ERROR);
		Database_Schema::debug(true);

		$db = Database::factory($db_url);

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$this->assert($db->connect(), "connecting to " . url::remove_password($db_url));

		$this->assert($db->table_exists($table));

		$object_table = $db->parse_create_table($sql);
		$table_name = $object_table->name();

		$this->assert("'$table_name' === '$table'");

		$db_table = $db->database_table($table_name);

		$result = Database_Schema::update($db, $db_table, $object_table);

		$this->assert($result === array(), php::dump($result) . ' === array()');

		echo basename(__FILE__) . ": Success.\n";
	}

	function test_3() {
		$db_url = zesk::get('DB_URL');

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

		log::level(log::ERROR);
		Database_Schema::debug(true);

		$db = Database::factory($db_url);

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$this->assert($db->connect(), "connecting to " . url::remove_password($db_url));

		$this->assert($db->table_exists($table));

		$object_table = $db->parse_create_table($sql);
		$table_name = $object_table->name();

		$this->assert("'$table_name' === '$table'");

		$db_table = $db->database_table($table_name);

		$result = Database_Schema::update($db, $db_table, $object_table);

		$this->assert($result === array(), php::dump($result) . ' === array()');

		$db->query("DROP TABLE IF EXISTS $table");

		echo basename(__FILE__) . ": Success.\n";
	}

	function test_4() {
		$db_url = zesk::get('DB_URL');

		DBSchemaTest4::$test_table = $table = 'temp_test_multi_create';

		DBSchemaTest4::$test_table2 = $table2 = 'temp_test_multi_create2';

		$db = Database::factory($db_url);

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query("DROP TABLE IF EXISTS $table2");

		zesk::set('test_table', $table);
		$object = zesk::factory('DBSchemaTest4');
		$result = Database_Schema::update_object($object);
		dump($object->database_schema());
		$this->log($result);
		$db->query($result);

		$this->assert($db->table_exists($table));
		$this->assert($db->table_exists($table2));

		$n_rows = $db->query_one("SELECT COUNT(*) AS X FROM $table", "X", null);
		$this->assert(intval($n_rows) === 1, "intval($n_rows) === 1");

		$db->query("DROP TABLE IF EXISTS $table2");

		$this->assert(!$db->table_exists($table2));

		$result = Database_Schema::updateObjectPath(__FILE__, zesk::get('DB_URL'), 'DBSchemaTest4');
		$db->query($result);

		$this->assert($db->table_exists($table));
		$this->assert($db->table_exists($table2));

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM $table", "X", null)) === 1);

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query("DROP TABLE IF EXISTS $table2");

		echo basename(__FILE__) . ": Success.\n";
	}

	function test_5() {
		$db_url = zesk::get('DB_URL');

		$db = Database::factory($db_url);

		DBSchemaTest5::$test_table = $table = 'keywords_test';
		$db->query("DROP TABLE IF EXISTS $table");

		$result = Database_Schema::updateObjectPath(__FILE__, zesk::get('DB_URL'), 'DBSchemaTest5');
		$db->query($result);

		$this->assert($db->table_exists($table));

		Database_Schema::debug(true);

		$result = Database_Schema::updateObjectPath(__FILE__, zesk::get('DB_URL'), 'DBSchemaTest5');
		$this->assert($result === array(), gettype($result));

		//$db->query("DROP TABLE IF EXISTS $table");


		echo basename(__FILE__) . ": Success.\n";
	}

	function test_6() {
		$db_url = zesk::get('DB_URL');

		$table = 'keywords_test';

		$db = Database::factory($db_url);

		$db->query("DROP TABLE IF EXISTS $table");

		DBSchemaTest6::$test_table = $table;
		$result = Database_Schema::updateObjectPath(__FILE__, zesk::get('DB_URL'), 'DBSchemaTest6');
		$db->query($result);

		$this->assert($db->table_exists($table));

		Database_Schema::debug(true);

		DBSchemaTest7::$test_table = $table;
		$result = Database_Schema::updateObjectPath(__FILE__, zesk::get('DB_URL'), 'DBSchemaTest7');

		$check_result = array(
			"ALTER IGNORE TABLE `keywords_test` ADD COLUMN `Proto` tinyint NOT NULL DEFAULT '0' AFTER `Protocol`"
		);

		$this->assert_arrays_equal($result, $check_result, true);

		//$db->query("DROP TABLE IF EXISTS $table");


		echo basename(__FILE__) . ": Success.\n";
	}

	function test_8() {
		$db_url = zesk::get('DB_URL');

		$table = 'bigint_test';

		$db = Database::factory($db_url);

		$db->query("DROP TABLE IF EXISTS $table");

		DBSchemaTest8::$test_table = $table;
		$result = Database_Schema::updateObjectPath(__FILE__, zesk::get('DB_URL'), 'DBSchemaTest8');
		$db->query($result);

		$this->assert($db->table_exists($table));

		Database_Schema::debug(true);
		log::file("php://stdout");

		$result = Database_Schema::updateObjectPath(__FILE__, zesk::get('DB_URL'), 'DBSchemaTest8');

		$this->assert_arrays_equal($result, array());

		$db->query("DROP TABLE IF EXISTS $table");

		echo basename(__FILE__) . ": Success.\n";
	}
}
class DBSchemaTest4 extends Object {

	public static $test_table = "";

	public static $test_table2 = "";

	function specification() {
		$table = self::$test_table;
		return array(
			"table" => $table,
			"fields" => "ID;Depth;CodeName;Name"
		);
	}

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
class DBSchemaTest5 extends Object {

	public static $test_table = null;

	function specification() {
		$table = self::$test_table;
		return array(
			"table" => $table,
			"fields" => "ID;Hash;Phrase;Created;Modified;Status;IsOrganic;LastUsed"
		);
	}

	function schema() {
		$table = self::$test_table;
		return "CREATE TABLE `${table}` (
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
class DBSchemaTest6 extends Object {

	public static $test_table = null;

	function specification() {
		$table = self::$test_table;
		return array(
			"table" => $table,
			"fields" => "ID;Hash;Protocol;Domain;Port;URI;QueryString;Title;Fragment;Frag"
		);
	}

	function schema() {
		$table = self::$test_table;
		return "CREATE TABLE `$table` (
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
class DBSchemaTest7 extends Object {

	public static $test_table = null;

	function specification() {
		$table = self::$test_table;
		return array(
			"table" => $table,
			"fields" => "ID;Hash;Protocol;Domain;Port;URI;QueryString;Title;Fragment;Frag;Proto"
		);
	}

	function schema() {
		$table = self::$test_table;
		return "CREATE TABLE `$table` (
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
class DBSchemaTest8 extends Object {

	public static $test_table = null;

	function specification() {
		$table = self::$test_table;
		return array(
			"table" => $table,
			"fields" => "ID;Hash;Size"
		);
	}

	function schema() {
		$table = self::$test_table;
		return "CREATE TABLE `$table` (
			`ID` int(11) unsigned NOT NULL auto_increment,
			`Hash` char(32) NOT NULL,
			`Size` bigint unsigned NOT NULL,
			PRIMARY KEY (ID)
		);";
	}
}

