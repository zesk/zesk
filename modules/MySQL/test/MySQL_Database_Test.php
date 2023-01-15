<?php
declare(strict_types=1);

namespace zesk;

use zesk\DatabaseUnitTest;

class MySQL_Database_Test extends DatabaseUnitTest {
	protected array $load_modules = [
		'MySQL',
	];

	public function test_types_compatible(): void {
		$mysql = $this->application->database_registry('mysql://root@localhost/mysql', [
			'connect' => false,
		]);
		$this->assertInstanceOf(Database::class, $mysql);
	}

	/**
	 *
	 * @return \mysql\Database
	 */
	public function database(): Database {
		$db = $this->application->database_registry();

		$this->assertTrue(in_array($db->type(), [
			'mysql',
			'mysqli',
		]), 'Type must be mysqli or mysql');
		return $db;
	}

	public function test_mysql_1(): void {
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

		$table = $db->parseCreateTable($sql, __METHOD__);

		$this->assertInstanceOf(Database_Table::class, $table);

		echo "Test created because preg_match dies on web2 with above input... due to pcre backtracking stack overflow ... or something like that\n";
	}

	public function test_mysql_funcs_1(): void {
		$db = $this->database();

		$test_table = $this->prepareTestTable('test_table');

		$db->databaseName();

		$filename = path($this->test_sandbox('dump.sql'));
		$options = [];
		$db->connect();
		$db->dump($filename, $options);

		$db->disconnect();
		$this->assertEquals($db->connected(), false);
		$success = false;

		try {
			/**
			 * Set auto_connect to false
			 */
			$db->query('SHOW TABLES', [
				'auto_connect' => false,
			]);
		} catch (Database_Exception_Connect $e) {
			$this->assertStringContainsString('Not connected', $e->getMessage());
			$success = true;
		}
		$this->assertEquals($db->connected(), false);
		$this->assertTrue($success);

		$db->connect();

		if ($db->can('create database')) {
			$url = null;
			//$db->createDatabase('mysql://test_user:test_pass@localhost/zesk_create_test_db');
		}

		$db->tablesCaseSensitive();

		$this->assertTrue($db->can(Database::FEATURE_LIST_TABLES));
		$this->assertTrue($db->can(Database::FEATURE_CREATE_DATABASE));

		$tables = $db->listTables();

		$debug = false;

		foreach ($tables as $table) {
			if ($debug) {
				$this->log('Showing table {table}', [
					'table' => $table,
				]);
			}
			$sql = $db->queryOne("SHOW CREATE TABLE $table", 1);
			if ($debug) {
				$this->log('Showing table {table} = {sql}', [
					'table' => $table,
					'sql' => $sql,
				]);
			}
			$this->assertStringStartsWith('CREATE TABLE', $sql);
			$this->assertStringContainsString($table, $sql);

			$dbTableObject = $db->parseCreateTable($sql, __METHOD__);
			$sql = $db->sql()->createTable($dbTableObject);
			if (!is_array($sql)) {
				$sqls = [
					$sql,
				];
			} else {
				$sqls = $sql;
			}
			$sql = first($sqls);
			$this->assertStringStartsWith('CREATE TABLE', $sql);
			$this->assertStringContainsString($table, $sql);

			$result = $db->tableInformation($table);
			$this->assertArrayHasKey('engine', $result);
			$this->assertArrayHasKey('created', $result);
			$this->assertInstanceOf(Timestamp::class, $result['created']);
			$this->assertArrayHasKey('updated', $result);
			$this->assertArrayHasKey('row_count', $result);
			$this->assertArrayHasKey('data_size', $result);
			$this->assertArrayHasKey('index_size', $result);
		}

		$table = null;

		$success = false;

		try {
			$table = 'testtable';
			$db->databaseTable($table);
		} catch (Database_Exception_Table_NotFound $e) {
			$success = true;
		}
		$this->assertTrue($success, "Table $table was found?");

		$table = 'testtable';
		$type = 'InnoDB';
		$db->sql()->alter_table_type($table, $type);

		$success = false;

		try {
			$table = new Database_Table($db, 'Foo');
			$index = new Database_Index($table);
			$index->type('DUCKY');
			$this->assertEquals($index->type(), Database_Index::TYPE_INDEX);
			$sql = $db->sql()->alter_table_index_drop($table, $index);
			$this->log($sql);
		} catch (Exception_Semantics $e) {
			$success = true;
		}
		$this->assertTrue($success);

		$table = new Database_Table($db, 'Foo');
		$index = new Database_Index($table, 'dude');
		$sql = $db->sql()->alter_table_index_drop($table, $index);
		$this->assertEquals($sql, 'ALTER TABLE `Foo` DROP INDEX `dude`');

		$table = new Database_Table($db, 'Foo');
		$index = new Database_Index($table, 'dude');
		$index->setType(Database_Index::TYPE_PRIMARY);

		$sql = $db->sql()->alter_table_index_drop($table, $index);
		$this->assertEquals($sql, 'ALTER TABLE `Foo` DROP PRIMARY KEY');

		$table = new Database_Table($db, 'testtable');
		$name = 'idx';
		$indexes = [
			'Foo' => 32,
		];
		$db->sql()->index_type($table, $name, Database_Index::TYPE_INDEX, $indexes);
		$db->sql()->index_type($table, $name, Database_Index::TYPE_UNIQUE, $indexes);
		$db->sql()->index_type($table, $name, Database_Index::TYPE_PRIMARY, $indexes);

		$table = new Database_Table($db, 'Foo');
		$table->columnAdd(new Database_Column($table, 'ID', [
			'sql_type' => 'integer unsigned',
		]));
		$table->columnAdd(new Database_Column($table, 'Name', [
			'sql_type' => 'varchar(32)',
		]));
		$index = new Database_Index($table, 'dude');
		$index->addColumn('ID');
		$index->setType(Database_Index::TYPE_PRIMARY);

		$sql = $db->sql()->alter_table_index_add($table, $index);
		$this->assertEquals($sql, 'ALTER TABLE `Foo` ADD PRIMARY KEY (`ID`)');

		$table = new Database_Table($db, $table_name = 'TestLine_' . __LINE__);
		$dbColOld = new Database_Column($table, 'Foo');
		$dbColOld->sql_type('varchar(32)');
		$dbColNew = new Database_Column($table, 'Foo');
		$dbColNew->sql_type('varchar(33)');
		$sql = $db->sql()->alter_table_change_column($table, $dbColOld, $dbColNew);
		$this->assertEquals($sql, "ALTER TABLE `$table_name` CHANGE COLUMN `Foo` `Foo` varchar(33) NULL");

		$query = 'SELECT NOW()';
		$result = $db->queryArray($query);
		$this->assertIsArray($result);

		$result = $db->query('SHOW TABLES');

		$success = false;

		try {
			$db->affectedRows($result);
		} catch (Exception_Semantics) {
			$success = true;
		}
		$this->assertTrue($success, 'affected rows should not work for a resultset');

		$db->query('DROP TABLE IF EXISTS foobar');
		$db->query('CREATE TABLE foobar ( id int PRIMARY KEY AUTO_INCREMENT )');
		$db->query('INSERT INTO foobar ( id ) VALUES (1)');
		$this->assertEquals(1, $db->insertID($result));
		$result = $db->query('DROP TABLE foobar');

		$db->free($result);
		$db->free($result);


		$sql = [];
		$db->mixed_query($sql);

		$sql = 'SHOW TABLES';
		$k = null;
		$v = null;
		$default = [];
		$db->queryArray($sql, $k, $v, $default);

		$db->now();

		$db->nowUTC();

		$tables = $db->listTables();
		$this->assertGreaterThan(0, count($tables), 'Test database should contain at least one table');

		foreach ($tables as $table) {
			$this->assertTrue($db->tableExists($table), "$table returned by listTables but does not exist?");
		}

		$word = 'foobar';
		$db->isReservedWord($word);

		$sql = 'CREATE TABLE Foo ( ID integer )';
		$db->parseCreateTable($sql, __METHOD__);

		$db = $this->application->database_registry();

		$url = $db->url();

		$this->assertTrue(!empty($url));

		$filler = 'ANTIDISESTABLISHMENTARIANISM';
		$safe_url = $db->safeURL($filler);
		$this->assertStringContainsString($filler, $safe_url);

		$table = new Database_Table($db, $table_name = 'TestTable' . __LINE__);
		$column = new Database_Column($table, 'hello');
		$column->sql_type('varchar(2)');
		$sqlType = null;
		$after_col = false;
		$sql = $db->sql()->alter_table_column_add($table, $column);
		$this->assertEquals($sql, "ALTER TABLE `$table_name` ADD COLUMN `hello` varchar(2) NULL");

		$table = new Database_Table($db, $table_name = 'TestTable' . __LINE__);
		$column = new Database_Column($table, 'hello');
		$column->sql_type('varchar(2)');

		$sql = $db->sql()->alter_table_column_drop($table, $column);
		$this->assertEquals($sql, "ALTER TABLE `$table_name` DROP COLUMN `hello`");

		$col = 'Hippy';
		$alias = 'Dippy';
		$sql = $db->sql()->columnAlias($col, $alias);
		$this->assertEquals($sql, '`Dippy`.`Hippy`');

		$db->transactionStart();

		$success = true;
		$db->transactionEnd($success);

		$table = 'random_table';
		$db->newDatabaseTable($table);

		$this->assertIsString($db->tablePrefix());
	}

	public function test_estimate_rows(): void {
		$db = $this->database();
		$this->assertTrue($db->tableExists('test_table'));
		$sql = 'SELECT * FROM test_table';
		$db->estimate_rows($sql);
	}
}
