<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class ORM_Schema_MySQL_Test extends Test_Unit {
	/**
	 *
	 * @var array
	 */
	protected array $load_modules = ["MySQL", "ORM", ];

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\PHPUnit_TestCase::initialize()
	 */
	public function initialize(): void {
		parent::initialize();
		include_once __DIR__ . '/ORM_Schema_MySQL_Test_Objects.php';
	}

	/**
	 *
	 * @return mysql\Database
	 */
	public function db() {
		$testx = $this->application->database_registry();

		$this->assert($testx->type() === "mysql");
		return $testx;
	}

	public function test_datetime_timestamp(): void {
		$sql0 = "CREATE TABLE test ( id integer unsigned NOT NULL, created datetime NULL ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
		$sql1 = "CREATE TABLE test ( id integer unsigned NOT NULL, created timestamp NULL ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";

		$db = $this->application->database_registry();

		$table0 = $db->parse_create_table($sql0, __METHOD__);
		$table1 = $db->parse_create_table($sql1, __METHOD__);

		$result = ORM_Schema::update($db, $table0, $table1, false);

		$datatype = $db->data_type();

		$this->assert_false($datatype->native_types_equal("timestamp", "datetime"));
		$this->assert_true($datatype->native_types_equal("int", "integer(12)"));

		$this->assert_arrays_equal($result, ["ALTER TABLE `test` CHANGE COLUMN `created` `created` timestamp NULL", ]);
	}

	public function test_primary_key_location(): void {
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
		$db = $this->application->database_registry();

		$result = $db->query("DROP TABLE IF EXISTS $table");
		$this->assert_equal($result, true);

		$this->assert_true(!$db->table_exists($table), "$table should not exist");

		foreach ($sql as $key => $create) {
			$result = ORM_Schema::table_synchronize($db, $create, false);
			$this->log("Running SQL {key} ({n}) {result}", ['key' => $key, 'n' => count($result), 'result' => $result]);
			$db->query($result);
		}
	}

	public function test_0(): void {
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

		ORM_Schema::debug(true);

		/* @var $db Database */
		$db = $this->application->database_registry();
		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$this->assert($db->connect(), "connecting to " . $db->safe_url());

		$this->assert($db->table_exists($table), "$db->table_exists($table)");

		$object_table = $db->parse_create_table($sql, __METHOD__);
		$table_name = $object_table->name();

		$this->assert_equal($table_name, $table);

		$db_table = $db->database_table($table_name);

		$result = ORM_Schema::update($db, $db_table, $object_table);

		$this->assert_equal($result, []);
	}

	public function test_1(): void {
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

		ORM_Schema::debug(true);

		$db = $this->application->database_registry();

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$this->assert($db->connect(), "connecting to " . $db->safe_url());
		$this->assert($db->table_exists($table));

		$object_table = $db->parse_create_table($sql, __METHOD__);

		$table_name = $object_table->name();

		$this->assert("'$table_name' === '$table'");

		$db_table = $db->database_table($table_name);

		$result = ORM_Schema::update($db, $db_table, $object_table);

		$this->assert($result === [], PHP::dump($result) . ' === array()');
	}

	public function test_2(): void {
		$table = 'temp_test_actions';

		// 2017-09 ActionTime datetime NOT NULL DEFAULT 0 no longer compatible across 5.6 and 5.7
		// Converting to case which should not matter
		$sql = "CREATE TABLE `$table` (
		`ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
		`ActionTime` datetime NULL,
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

		ORM_Schema::debug(true);

		$db = $this->application->database_registry();

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$this->assert($db->connect(), "connecting to " . $db->safe_url());

		$this->assert($db->table_exists($table));

		$object_table = $db->parse_create_table($sql, __METHOD__);
		$table_name = $object_table->name();

		$this->assert("'$table_name' === '$table'");

		$db_table = $db->database_table($table_name);

		$result = ORM_Schema::update($db, $db_table, $object_table);

		$this->assert($result === [], PHP::dump($result) . ' === array()');
	}

	public function test_3(): void {
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

		ORM_Schema::debug(true);

		$db = $this->application->database_registry();

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$this->assert($db->connect(), "connecting to " . $db->safe_url());

		$this->assert($db->table_exists($table));

		$object_table = $db->parse_create_table($sql, __METHOD__);
		$table_name = $object_table->name();

		$this->assert("'$table_name' === '$table'");

		$db_table = $db->database_table($table_name);

		$result = ORM_Schema::update($db, $db_table, $object_table);

		$this->assert($result === [], PHP::dump($result) . ' === array()');

		$db->query("DROP TABLE IF EXISTS $table");

		$this->log(__FUNCTION__);
	}

	public function test_4(): void {
		DBSchemaTest4::$test_table = $table = 'temp_test_multi_create';

		DBSchemaTest4::$test_table2 = $table2 = 'temp_test_multi_create2';

		$db = $this->application->database_registry();

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query("DROP TABLE IF EXISTS $table2");

		$object = new DBSchemaTest4($this->application);

		$result = ORM_Schema::update_object($object);
		$this->log($result);
		$db->query($result);

		$this->assert($db->table_exists($table));
		$this->assert($db->table_exists($table2));

		$n_rows = $db->query_one("SELECT COUNT(*) AS X FROM $table", "X", null);
		$this->assert(intval($n_rows) === 1, "intval($n_rows) === 1");

		$db->query("DROP TABLE IF EXISTS $table2");

		$this->assert(!$db->table_exists($table2));

		$object = new DBSchemaTest4($this->application);
		$result = ORM_Schema::update_object($object);
		$db->query($result);

		$this->assert($db->table_exists($table));
		$this->assert($db->table_exists($table2));

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM $table", "X", null)) === 1);

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query("DROP TABLE IF EXISTS $table2");

		echo basename(__FILE__) . ": Success.\n";
	}

	public function test_5(): void {
		$db = $this->application->database_registry();

		DBSchemaTest5::$test_table = $table = 'keywords_test';
		$db->query("DROP TABLE IF EXISTS $table");

		$object = new DBSchemaTest5($this->application);
		$result = ORM_Schema::update_object($object);
		$db->query($result);

		$this->assert($db->table_exists($table));

		ORM_Schema::debug(true);

		$object = new DBSchemaTest5($this->application);
		$result = ORM_Schema::update_object($object);
		$this->assert($result === [], gettype($result));

		//$db->query("DROP TABLE IF EXISTS $table");

		echo basename(__FILE__) . ": Success.\n";
	}

	public function test_6(): void {
		$table = 'keywords_test';

		$db = $this->application->database_registry();

		$db->query("DROP TABLE IF EXISTS $table");

		DBSchemaTest6::$test_table = $table;
		$object = new DBSchemaTest6($this->application);
		$result = ORM_Schema::update_object($object);
		$db->query($result);

		$this->assert($db->table_exists($table));

		ORM_Schema::debug(true);

		DBSchemaTest7::$test_table = $table;
		$object = new DBSchemaTest7($this->application);
		$result = ORM_Schema::update_object($object);

		$check_result = ["ALTER TABLE `keywords_test` ADD COLUMN `Proto` tinyint NOT NULL DEFAULT 0 AFTER `Protocol`", ];

		$this->assert_arrays_equal($result, $check_result, true);

		//$db->query("DROP TABLE IF EXISTS $table");

		echo basename(__FILE__) . ": Success.\n";
	}

	public function test_8(): void {
		$table = 'bigint_test';

		$db = $this->application->database_registry();

		$db->query("DROP TABLE IF EXISTS $table");

		DBSchemaTest8::$test_table = $table;
		$object = new DBSchemaTest8($this->application);
		$result = ORM_Schema::update_object($object);
		$db->query($result);

		$this->assert($db->table_exists($table));

		ORM_Schema::debug(true);

		$object = new DBSchemaTest8($this->application);
		$result = ORM_Schema::update_object($object);

		$this->assert_arrays_equal($result, []);

		$db->query("DROP TABLE IF EXISTS $table");

		echo basename(__FILE__) . ": Success.\n";
	}

	public function test_schema0(): void {
		$updates = ORM_Schema::update_object($this->application->orm_factory(__NAMESPACE__ . "\\" . 'DBSchemaTest_columns_0'));
		dump($updates);
		//TODO - not sure what this is testing but perhaps the SQL caused errors previously?
		$updates = ORM_Schema::update_object($this->application->orm_factory(__NAMESPACE__ . "\\" . 'DBSchemaTest_columns_1'));
		dump($updates);
		//TODO - not sure what this is testing but perhaps the SQL caused errors previously?
	}
}
