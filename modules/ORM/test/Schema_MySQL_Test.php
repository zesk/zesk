<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk\ORM;

use zesk\Database;

/**
 *
 * @author kent
 *
 */
class Schema_MySQL_Test extends ORMUnitTest {
	/**
	 *
	 * @var array
	 */
	protected array $load_modules = ['MySQL', 'ORM', ];

	public function initialize(): void {
		include_once __DIR__ . '/Schema_MySQL_Test_Objects.php';
	}

	/**
	 *
	 * @return Database
	 */
	public function db(): Database {
		$testx = $this->application->databaseRegistry();

		$this->assertEquals('mysql', $testx->type());
		return $testx;
	}

	public function test_datetime_timestamp(): void {
		$sql0 = 'CREATE TABLE test ( id integer unsigned NOT NULL, created datetime NULL ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci';
		$sql1 = 'CREATE TABLE test ( id integer unsigned NOT NULL, created timestamp NULL ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci';

		$db = $this->application->databaseRegistry();

		$table0 = $db->parseCreateTable($sql0, __METHOD__);
		$table1 = $db->parseCreateTable($sql1, __METHOD__);

		$result = Schema::update($db, $table0, $table1, false);

		$datatype = $db->data_type();

		$this->assertFalse($datatype->native_types_equal('timestamp', 'datetime'));
		$this->assertTrue($datatype->native_types_equal('int', 'integer(12)'));

		$this->assertEquals(['ALTER TABLE `test` CHANGE COLUMN `created` `created` timestamp NULL', ], $result);
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
		$db = $this->application->databaseRegistry();

		$result = $db->query("DROP TABLE IF EXISTS $table");
		$this->assertEquals(null, $result->resource());

		$this->assertFalse($db->tableExists($table), "$table should not exist");

		foreach ($sql as $key => $create) {
			$result = Schema::tableSynchronize($db, $create, false);
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

		Schema::debug(true);

		$db = $this->application->databaseRegistry();
		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$db->connect();
		$this->assertTrue($db->connected(), 'connecting to ' . $db->safeURL());

		$this->assertTrue($db->tableExists($table), "\$db->tableExists($table)");

		$object_table = $db->parseCreateTable($sql, __METHOD__);
		$table_name = $object_table->name();

		$this->assertEquals($table_name, $table);

		$db_table = $db->databaseTable($table_name);

		Schema::$debug = true;
		$result = Schema::update($db, $db_table, $object_table);

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

		Schema::debug(true);

		$db = $this->application->databaseRegistry();

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$db->connect();
		$this->assertTrue($db->connected(), 'connecting to ' . $db->safeURL());
		$this->assertTrue($db->tableExists($table));

		$object_table = $db->parseCreateTable($sql, __METHOD__);

		$table_name = $object_table->name();

		$this->assertEquals($table, $table_name);

		$db_table = $db->databaseTable($table_name);

		$result = Schema::update($db, $db_table, $object_table);

		$this->assertEquals([], $result);
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

		Schema::debug(true);

		$db = $this->application->databaseRegistry();

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$db->connect();
		$this->assertTrue($db->connected(), 'connecting to ' . $db->safeURL());

		$this->assertTrue($db->tableExists($table));

		$object_table = $db->parseCreateTable($sql, __METHOD__);
		$table_name = $object_table->name();

		$this->assertEquals($table, $table_name);

		$db_table = $db->databaseTable($table_name);

		$result = Schema::update($db, $db_table, $object_table);

		$this->assertEquals([], $result);
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

		Schema::debug(true);

		$db = $this->application->databaseRegistry();

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($sql);

		$db->connect();
		$this->assertTrue($db->connected(), 'connecting to ' . $db->safeURL());

		$this->assertTrue($db->tableExists($table));

		$object_table = $db->parseCreateTable($sql, __METHOD__);
		$table_name = $object_table->name();

		$this->assertEquals($table_name, $table);

		$db_table = $db->databaseTable($table_name);

		$result = Schema::update($db, $db_table, $object_table);

		$this->assertEquals([], $result);

		$db->query("DROP TABLE IF EXISTS $table");
	}

	public function test_4(): void {
		DBSchemaTest4::$test_table = $table = 'temp_test_multi_create';

		DBSchemaTest4::$test_table2 = $table2 = 'temp_test_multi_create2';

		$db = $this->application->databaseRegistry();

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query("DROP TABLE IF EXISTS $table2");

		$object = new DBSchemaTest4($this->application);

		$result = Schema::update_object($object);
		$db->queries($result);
		$this->log($result);

		$this->assertTrue($db->tableExists($table));
		$this->assertTrue($db->tableExists($table2));

		$n_rows = $db->queryOne("SELECT COUNT(*) AS X FROM $table", 'X');
		$this->assertEquals(1, intval($n_rows));

		$db->query("DROP TABLE IF EXISTS $table2");

		$this->assertFalse($db->tableExists($table2));

		$object = new DBSchemaTest4($this->application);
		$result = Schema::update_object($object);
		$db->queries($result);

		$this->assertTrue($db->tableExists($table));
		$this->assertTrue($db->tableExists($table2));

		$this->assertEquals(1, intval($db->queryOne("SELECT COUNT(*) AS X FROM $table", 'X')));

		$db->query("DROP TABLE IF EXISTS $table");
		$db->query("DROP TABLE IF EXISTS $table2");
	}

	public function test_5(): void {
		$db = $this->application->databaseRegistry();

		DBSchemaTest5::$test_table = $table = 'keywords_test';
		$db->query("DROP TABLE IF EXISTS $table");

		$object = new DBSchemaTest5($this->application);
		$result = Schema::update_object($object);
		$db->queries($result);

		$this->assertTrue($db->tableExists($table));

		Schema::debug(true);

		$object = new DBSchemaTest5($this->application);
		$result = Schema::update_object($object);
		$this->assertEquals([], $result);
	}

	public function test_6(): void {
		$table = 'keywords_test';

		$db = $this->application->databaseRegistry();

		$db->query("DROP TABLE IF EXISTS $table");

		DBSchemaTest6::$test_table = $table;
		$object = new DBSchemaTest6($this->application);
		$result = Schema::update_object($object);
		$db->queries($result);

		$this->assertTrue($db->tableExists($table));

		Schema::debug(true);

		DBSchemaTest7::$test_table = $table;
		$object = new DBSchemaTest7($this->application);
		$result = Schema::update_object($object);

		$check_result = ['ALTER TABLE `keywords_test` ADD COLUMN `Proto` tinyint NOT NULL DEFAULT 0 AFTER `Protocol`', ];

		$this->assertEquals($check_result, $result);

		$db->query("DROP TABLE IF EXISTS $table");
	}

	public function test_8(): void {
		$table = 'bigint_test';

		$db = $this->application->databaseRegistry();

		$db->query("DROP TABLE IF EXISTS $table");

		DBSchemaTest8::$test_table = $table;
		$object = new DBSchemaTest8($this->application);
		$result = Schema::update_object($object);
		$db->queries($result);

		$this->assertTrue($db->tableExists($table));

		Schema::debug(true);

		$object = new DBSchemaTest8($this->application);
		$result = Schema::update_object($object);

		$this->assertEquals([], $result);

		$db->query("DROP TABLE IF EXISTS $table");
	}

	public function test_schema0(): void {
		$updates = Schema::update_object($this->application->ormFactory(__NAMESPACE__ . '\\' . 'DBSchemaTest_columns_0'));
		$expected = [
			"CREATE TABLE `DBSchemaTest_columns_0` (\n\t`ID` int(11) unsigned AUTO_INCREMENT PRIMARY KEY NOT NULL,\n\t`Hash` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,\n\t`Protocol` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',\n\t`Domain` int(11) unsigned NULL DEFAULT NULL,\n\t`Port` smallint(11) unsigned NULL,\n\t`URI` int(11) unsigned NULL DEFAULT NULL,\n\t`QueryString` int(11) unsigned NULL DEFAULT NULL,\n\t`Title` int(11) unsigned NULL,\n\t`Fragment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,\n\t`Frag` int(11) unsigned NULL\n) ",
			'ALTER TABLE `DBSchemaTest_columns_0` ADD INDEX `domain` TYPE BTREE (`Domain`)',
			'ALTER TABLE `DBSchemaTest_columns_0` ADD INDEX `title` TYPE BTREE (`Title`)',
			'ALTER TABLE `DBSchemaTest_columns_0` ADD UNIQUE `Hash` TYPE BTREE (`Hash`)', '-- database type mysqli',
			"-- sql MySQL\Database_SQL",
		];
		$this->assertEquals($expected, $updates);
		//TODO - not sure what this is testing but perhaps the SQL caused errors previously?
		$updates = Schema::update_object($this->application->ormFactory(__NAMESPACE__ . '\\' . 'DBSchemaTest_columns_1'));
		$expected = [
			"CREATE TABLE `DBSchemaTest_columns_1` (\n\t`ID` int(11) unsigned AUTO_INCREMENT NOT NULL,\n\t`Hash` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,\n\t`Protocol` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',\n\t`Domain` int(11) unsigned NULL DEFAULT NULL,\n\t`Port` smallint(11) unsigned NULL,\n\t`URI` int(11) unsigned NULL DEFAULT NULL\n) ",
			'-- database type mysqli', "-- sql MySQL\Database_SQL",
		];
		$this->assertEquals($expected, $updates);
	}
}