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
	protected array $load_modules = ['MySQL', 'ORM', ];

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
	 * @return Database
	 */
	public function db() {
		$testx = $this->application->database_registry();

		$this->assert($testx->type() === 'mysql');
		return $testx;
	}

	public function test_datetime_timestamp(): void {
		$sql0 = 'CREATE TABLE test ( id integer unsigned NOT NULL, created datetime NULL ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci';
		$sql1 = 'CREATE TABLE test ( id integer unsigned NOT NULL, created timestamp NULL ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci';

		$db = $this->application->database_registry();

		$table0 = $db->parseCreateTable($sql0, __METHOD__);
		$table1 = $db->parseCreateTable($sql1, __METHOD__);

		$result = ORM_Schema::update($db, $table0, $table1, false);

		$datatype = $db->data_type();

		$this->assert_false($datatype->native_types_equal('timestamp', 'datetime'));
		$this->assert_true($datatype->native_types_equal('int', 'integer(12)'));

		$this->assert_arrays_equal($result, ['ALTER TABLE `test` CHANGE COLUMN `created` `created` timestamp NULL', ]);
	}

	public function test_primary_key_location(): void {
		$sql['base'] = 'CREATE TABLE `{table}` (
			`id` integer unsigned NOT NULL,
			`name` varchar(255) NOT NULL DEFAULT \'\'
		);';
		$sql['separate'] = 'CREATE TABLE `{table}` (
			`id` integer unsigned NOT NULL AUTO_INCREMENT,
			`name` varchar(255) NOT NULL DEFAULT \'\',
			PRIMARY KEY (`id`)
		);';
		$sql['together'] = 'CREATE TABLE `{table}` (
			`id` integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`name` varchar(255) NOT NULL DEFAULT \'\',
		);';

		$table = __FUNCTION__;
		$sql = map($sql, compact('table'));
		$db = $this->application->database_registry();

		$result = $db->query("DROP TABLE IF EXISTS $table");
		$this->assert_equal($result->resource(), null);

		$this->assertFalse($db->tableExists($table), "$table should not exist");

		foreach ($sql as $key => $create) {
			$result = ORM_Schema::tableSynchronize($db, $create, false);
			$this->log('Running SQL {key} ({n}) {result}', ['key' => $key, 'n' => count($result), 'result' => $result]);
			$db->queries($result);
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

		$db->connect();
		$this->assert($db->connected(), 'connecting to ' . $db->safeURL());

		$this->assert($db->tableExists($table), "$db->tableExists($table)");

		$object_table = $db->parseCreateTable($sql, __METHOD__);
		$table_name = $object_table->name();

		$this->assert_equal($table_name, $table);

		$db_table = $db->databaseTable($table_name);

		ORM_Schema::$debug = true;
		$result = ORM_Schema::update($db, $db_table, $object_table);

		$this->assertEquals([], $result);
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

		$db->connect();
		$this->assert($db->connected(), 'connecting to ' . $db->safeURL());
		$this->assert($db->tableExists($table));

		$object_table = $db->parseCreateTable($sql, __METHOD__);

		$table_name = $object_table->name();

		$this->assert("'$table_name' === '$table'");

		$db_table = $db->databaseTable($table_name);

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

		$db->connect();
		$this->assert($db->connected(), 'connecting to ' . $db->safeURL());

		$this->assert($db->tableExists($table));

		$object_table = $db->parseCreateTable($sql, __METHOD__);
		$table_name = $object_table->name();

		$this->assert("'$table_name' === '$table'");

		$db_table = $db->databaseTable($table_name);

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

		$db->connect();
		$this->assert($db->connected(), 'connecting to ' . $db->safeURL());

		$this->assert($db->tableExists($table));

		$object_table = $db->parseCreateTable($sql, __METHOD__);
		$table_name = $object_table->name();

		$this->assert("'$table_name' === '$table'");

		$db_table = $db->databaseTable($table_name);

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
		$db->queries($result);
		$this->log($result);

		$this->assert($db->tableExists($table));
		$this->assert($db->tableExists($table2));

		$n_rows = $db->queryOne("SELECT COUNT(*) AS X FROM $table", 'X', null);
		$this->assert(intval($n_rows) === 1, "intval($n_rows) === 1");

		$db->query("DROP TABLE IF EXISTS $table2");

		$this->assert(!$db->tableExists($table2));

		$object = new DBSchemaTest4($this->application);
		$result = ORM_Schema::update_object($object);
		$db->queries($result);
		$this->log($result);

		$this->assert($db->tableExists($table));
		$this->assert($db->tableExists($table2));

		$this->assert(intval($db->queryOne("SELECT COUNT(*) AS X FROM $table", 'X', null)) === 1);

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query("DROP TABLE IF EXISTS $table2");
	}

	public function test_5(): void {
		$db = $this->application->database_registry();

		DBSchemaTest5::$test_table = $table = 'keywords_test';
		$db->query("DROP TABLE IF EXISTS $table");

		$object = new DBSchemaTest5($this->application);
		$result = ORM_Schema::update_object($object);
		$db->queries($result);

		$this->assert($db->tableExists($table));

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
		$db->queries($result);

		$this->assert($db->tableExists($table));

		ORM_Schema::debug(true);

		DBSchemaTest7::$test_table = $table;
		$object = new DBSchemaTest7($this->application);
		$result = ORM_Schema::update_object($object);

		$check_result = ['ALTER TABLE `keywords_test` ADD COLUMN `Proto` tinyint NOT NULL DEFAULT 0 AFTER `Protocol`', ];

		$this->assert_arrays_equal($result, $check_result, true);

		$db->query("DROP TABLE IF EXISTS $table");
	}

	public function test_8(): void {
		$table = 'bigint_test';

		$db = $this->application->database_registry();

		$db->query("DROP TABLE IF EXISTS $table");

		DBSchemaTest8::$test_table = $table;
		$object = new DBSchemaTest8($this->application);
		$result = ORM_Schema::update_object($object);
		$db->queries($result);

		$this->assert($db->tableExists($table));

		ORM_Schema::debug(true);

		$object = new DBSchemaTest8($this->application);
		$result = ORM_Schema::update_object($object);

		$this->assert_arrays_equal($result, []);

		$db->query("DROP TABLE IF EXISTS $table");

		echo basename(__FILE__) . ": Success.\n";
	}

	public function test_schema0(): void {
		$updates = ORM_Schema::update_object($this->application->ormFactory(__NAMESPACE__ . '\\' . 'DBSchemaTest_columns_0'));
		dump($updates);
		//TODO - not sure what this is testing but perhaps the SQL caused errors previously?
		$updates = ORM_Schema::update_object($this->application->ormFactory(__NAMESPACE__ . '\\' . 'DBSchemaTest_columns_1'));
		dump($updates);
		//TODO - not sure what this is testing but perhaps the SQL caused errors previously?
	}
}
