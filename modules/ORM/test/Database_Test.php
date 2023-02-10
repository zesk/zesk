<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM;

use zesk\Database;
use zesk\Database\QueryResult;
use zesk\Database_Column;
use zesk\Database_Exception_Duplicate;
use zesk\Database_Exception_SQL;
use zesk\Database_Exception_Table_NotFound;
use zesk\Database_Table;
use zesk\Exception_Key;
use zesk\Exception_Semantics;
use zesk\File;
use zesk\PHP;
use zesk\StringTools;
use zesk\DatabaseUnitTest;
use function random_int;

/**
 *
 * @author kent
 *
 */
class Database_Test extends DatabaseUnitTest {
	public static null|self $test = null;

	protected array $load_modules = [
		'MySQL', 'ORM',
	];

	public function initialize(): void {
		require_once __DIR__ . '/Database_Test_Objects.php';
		$this->schemaSynchronize([DBQueryObjectTest::class]);
	}

	// 	function test_table_indexes() {
	// 		$table = "db_table_indexes_test";

	// 		$db = $this->application->databaseRegistry();
	// 		$this->test_table($table);

	// 		$dbname = "";
	// 		$result = $db->table_indexes($table, $dbname);

	// 		dump($result);

	// 		$this->assertEquals(array(
	// 			"id"
	// 		), $result["PRIMARY"] ?? null);
	// 		$this->assertEquals(array(
	// 			"foo"
	// 		), $result["f"] ?? null);
	// 	}
	public function test_table_columns(): void {
		$table = 'db_table_indexes_test';
		$db = $this->application->databaseRegistry();

		$this->prepareTestTable($table);

		$result = $db->tableColumns($table);

		$compare_table = new Database_Table($db, $table);
		$compare_result = [
			'id' => new Database_Column($compare_table, 'id', [
				'sql_type' => 'int(11) unsigned', 'not null' => true, 'serial' => true,
			]), 'foo' => new Database_Column($compare_table, 'foo', [
				'sql_type' => 'int(11)', 'not null' => true, 'serial' => false,
			]),
		];

		$this->assertEquals($result, $compare_result);
	}

	/**
	 * @return void
	 */
	public function testMissingTable(): void {
		$db = $this->application->databaseRegistry();
		$this->expectException(Database_Exception_Table_NotFound::class);
		$db->tableColumns('table_does_not_exist');
	}

	public function test_query_object(): void {
		$db = $this->application->databaseRegistry();
		$table_name = 'query_object_test';
		$this->prepareTestTable($table_name);

		$db->insert($table_name, [
			'foo' => 100,
		]);
		$db->insert($table_name, [
			'foo' => 200,
		]);
		$db->insert($table_name, [
			'foo' => 300,
		]);

		$sql = "SELECT * FROM $table_name";
		$k = 'id';

		self::$test = $this;

		$result = $db->queryArray($sql, $k);
		foreach ($result as $k => $v) {
			$result[$k] = new DBQueryObjectTest($this->application, $v);
		}

		$compare_result[1] = new DBQueryObjectTest($this->application, [
			'id' => 1, 'foo' => 100,
		]);
		$compare_result[2] = new DBQueryObjectTest($this->application, [
			'id' => 2, 'foo' => 200,
		]);
		$compare_result[3] = new DBQueryObjectTest($this->application, [
			'id' => 3, 'foo' => 300,
		]);

		$this->assertEquals($result, $compare_result);
		self::$test = null;
	}

	public function test_query_array_index(): void {
		$db = $this->application->databaseRegistry();
		$table = 'query_array_index_test';

		$this->prepareTestTable($table);

		$sql = "SELECT * FROM $table ORDER BY Foo ASC";
		$k = 0;
		$v = 1;
		$result = $db->queryArrayIndex($sql, $k, $v);

		$this->assertEquals([], $result);

		$db->insert($table, [
			'foo' => 10,
		]);
		$db->insert($table, [
			'foo' => 20,
		]);
		$db->insert($table, [
			'foo' => 30,
		]);

		$result = $db->queryArrayIndex($sql);
		$this->assertEquals([
			[
				0 => '1', 1 => '10',
			], [
				0 => '2', 1 => '20',
			], [
				0 => '3', 1 => '30',
			],
		], $result);

		$result = $db->queryArrayIndex($sql, $k, null);
		$this->assertEquals([
			'1' => [
				0 => '1', 1 => '10',
			], '2' => [
				0 => '2', 1 => '20',
			], '3' => [
				0 => '3', 1 => '30',
			],
		], $result);

		$result = $db->queryArrayIndex($sql, null, $v);

		$this->assertEquals([
			'10', '20', '30',
		], $result);
	}

	public function test_query_array(): void {
		$db = $this->application->databaseRegistry();

		$table = 'query_array_test';

		$this->prepareTestTable($table);

		$sql = "SELECT * FROM $table ORDER BY Foo ASC";
		$k = 'id';
		$v = 'foo';

		$result = $db->queryArray($sql, $k, $v);

		$this->assertEquals([], $result);

		$db->insert($table, [
			'foo' => 10,
		]);
		$db->insert($table, [
			'foo' => 20,
		]);
		$db->insert($table, [
			'foo' => 30,
		]);

		$result = $db->queryArray($sql);
		$this->assertEquals([
			[
				'id' => '1', 'foo' => '10',
			], [
				'id' => '2', 'foo' => '20',
			], [
				'id' => '3', 'foo' => '30',
			],
		], $result);

		$result = $db->queryArray($sql, $k, null);
		$this->assertEquals([
			'1' => [
				'id' => '1', 'foo' => '10',
			], '2' => [
				'id' => '2', 'foo' => '20',
			], '3' => [
				'id' => '3', 'foo' => '30',
			],
		], $result);

		$result = $db->queryArray($sql, null, 'foo');

		$this->assertEquals([
			'10', '20', '30',
		], $result);

		$result = $db->queryArray($sql, null, 'foo');

		$this->assertEquals([
			'10', '20', '30',
		], $result);

		$result = $db->queryArray($sql, 'foo', 'id');

		$this->assertEquals([
			'10' => '1', '20' => '2', '30' => '3',
		], $result);

		$result = $db->queryArray($sql, 'foo', null);

		$this->assertEquals([
			'10' => [
				'id' => '1', 'foo' => '10',
			], '20' => [
				'id' => '2', 'foo' => '20',
			], '30' => [
				'id' => '3', 'foo' => '30',
			],
		], $result);
	}

	/**
	 * @throws Database_Exception_Table_NotFound
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 */
	public function test_affected_rows(): void {
		$db = $this->application->databaseRegistry();

		$table = last(explode('::', __METHOD__));
		$db->query("DROP TABLE IF EXISTS $table");
		$db->query("CREATE TABLE $table ( id int )");
		$total_rows = random_int(50, 100);
		for ($i = 0; $i < $total_rows; $i++) {
			$db->query("INSERT INTO $table ( id ) VALUES ( $i )");
		}
		$result = $db->query("UPDATE $table SET id = id + 1");
		$this->assertEquals($total_rows, $db->affectedRows($result));
		$db->query("DROP TABLE $table");
	}

	public function test_auto_table_names(): void {
		$db = $this->application->databaseRegistry();

		$db->setAutoTableNames(true);
		$db->setAutoTableNames(false);
	}

	public static function data_setAutoTableNamesOptions(): array {
		return [
			[['name' => 'value'], ],
		];
	}

	/**
	 * @param array $set
	 * @return void
	 * @dataProvider data_setAutoTableNamesOptions
	 */
	public function test_setAutoTableNamesOptions(array $set): void {
		$db = $this->application->databaseRegistry();

		$db->setAutoTableNamesOptions($set);
		$this->assertEquals($set, $db->autoTableNamesOptions());
	}

	public function test_auto_table_names_replace(): void {
		$db = $this->application->databaseRegistry();
		$this->application->objects->setMap(['ponies' => User::class]);

		$this->assertEquals('SELECT * FROM `User`', $db->autoTableRename('SELECT * FROM {ponies}'));
	}

	public function test_bytesUsed(): void {
		$db = $this->application->databaseRegistry();

		//echo DB_URL ."\n";
		//$db->debug();
		$table = null;
		$default = null;
		$this->assertGreaterThan(0, $db->bytesUsed());

		$table = $this->prepareTestTable('bytesUsed');
		$status = $db->queryOne('SHOW TABLE STATUS LIKE \'bytesUsed\'');

		$this->assertArrayHasKey('Name', $status);
		$this->assertArrayHasKey('Rows', $status);
		$this->assertArrayHasKey('Data_length', $status);
		$this->assertArrayHasKey('Index_length', $status);
	}

	public function test_connect(): void {
		$db = $this->application->databaseRegistry();

		$db->connect();
	}

	public function test_reconnect(): void {
		$db = $this->application->databaseRegistry();
		$db->reconnect();
	}

	public function test_query_one(): void {
		$db = $this->application->databaseRegistry();

		$table = 'select_map_test';

		$this->prepareTestTable($table);

		$where = [
			'foo' => 2,
		];
		$db->insert($table, $where);

		$this->assertEquals(1, intval($db->queryOne("SELECT COUNT(*) AS X FROM $table", 'X')));

		$where = [
			'foo' => 3,
		];
		$db->insert($table, $where);

		$this->assertEquals(2, intval($db->queryOne("SELECT COUNT(*) AS X FROM $table", 'X')));

		$db->delete($table, $where);

		$this->assertEquals(1, intval($db->queryOne("SELECT COUNT(*) AS X FROM $table", 'X')));
	}

	public function test_dump(): void {
		$db = $this->application->databaseRegistry();

		$db->dump(File::temporary($this->sandbox(), '.dbdump'));
	}

	public function test_fetch_array(): void {
		$db = $this->application->databaseRegistry();

		$res = $db->query('SELECT 1 AS A,2 AS B,3 as C,4 AS D,5 as E,\'string\' AS F');
		$db->fetchArray($res);
	}

	public function test_getLock(): void {
		$db = $this->application->databaseRegistry();

		$name = md5(microtime());
		$wait_seconds = 0;
		$db->getLock($name, $wait_seconds);
		$db->releaseLock($name);
	}

	public function test_insert(): void {
		$db = $this->application->databaseRegistry();

		$table_name = 'insert_test';

		$this->prepareTestTable($table_name);

		$this->assertEquals(0, intval($db->queryOne("SELECT COUNT(*) AS X FROM `$table_name`", 'X')));

		$t = $table_name;
		$arr = [
			'foo' => 1,
		];
		$db->insert($t, $arr);

		$this->assertEquals(1, intval($db->queryOne("SELECT COUNT(*) AS X FROM `$table_name`", 'X')));

		$t = $table_name;
		$arr = [
			'foo' => 2,
		];
		$db->insert($t, $arr);

		$this->assertEquals(2, intval($db->queryOne("SELECT COUNT(*) AS X FROM `$table_name`", 'X')));

		$caught = false;

		try {
			$t = $table_name;
			$arr = [
				'foo' => 2,
			];
			$db->insert($t, $arr);
		} catch (Database_Exception_Duplicate $e) {
			$caught = true;
		}
		$this->assertTrue($caught);
	}

	public function test_query(): void {
		$db = $this->application->databaseRegistry();

		$table_name = 'query_test';

		$this->prepareTestTable($table_name);

		$sql = "SELECT * FROM $table_name";
		$result = $db->query($sql);
		$this->log('$db->query result is ' . gettype($result));
		$this->assertInstanceOf(QueryResult::class, $result);

		$sql = "UPDATE $table_name SET Foo=Foo+1";
		$result = $db->query($sql);
		$this->assertInstanceOf(QueryResult::class, $result);
	}

	public function test_query_integer(): void {
		$db = $this->application->databaseRegistry();

		$sql = 'SELECT 23 * 54 AS X';
		$this->assertEquals(23 * 54, $db->queryInteger($sql, 'X'));
		$this->expectException(Exception_Key::class);
		$this->assertEquals(0, $db->queryInteger($sql, 'Y'));
	}

	public function test_query_one1(): void {
		$db = $this->application->databaseRegistry();

		$table_name = 'query_object_test';

		$this->prepareTestTable($table_name);

		$n = random_int(100, 1000);
		for ($i = 1; $i <= $n; $i++) {
			$db->insert($table_name, [
				'foo' => $i * 100,
			]);
		}
		$sql = "SELECT MAX(ID) AS X FROM $table_name";
		$this->assertEquals($n, intval($db->queryOne($sql, 'X')));
		$sql = "SELECT MIN(ID) AS X FROM $table_name";
		$this->assertEquals(1, intval($db->queryOne($sql, 'X')));
		$sql = "SELECT MAX(Foo) AS X FROM $table_name";
		$this->assertEquals($n * 100, intval($db->queryOne($sql, 'X')));
		$sql = "SELECT MIN(Foo) AS X FROM $table_name";
		$this->assertEquals(100, intval($db->queryOne($sql, 'X')));
	}

	/**
	 * @always_fail
	 */
	public function test_releaseLock(): void {
		$db = $this->application->databaseRegistry();
		$name = 'fail';
		$this->expectException(Exception_Semantics::class);
		$db->releaseLock($name);
	}

	public function test_locks(): void {
		$db = $this->application->databaseRegistry();

		$name = __FUNCTION__;
		$db->getLock($name);
		$db->getLock($name);
		$db->getLock($name);

		$db->releaseLock($name);

		$intversion = 0;
		if ($db instanceof \MySQL\Database) {
			$version = $db->version();
			[$maj, $min, $patch, $rest] = explode('.', $version, 4) + array_fill(0, 4, 0);
			$intversion = intval($maj) * 10000 + intval($min) * 100 + intval($patch);
		}
		if ($intversion >= 50700) {
			$db->releaseLock($name);
		} else {
			$this->expectException(Exception_Semantics::class);
		}
		$db->releaseLock($name);
	}

	public function test_replace(): void {
		$db = $this->application->databaseRegistry();

		$table_name = 'replace_test';

		$this->prepareTestTable($table_name);

		$this->assertEquals(0, intval($db->queryOne("SELECT COUNT(*) AS X FROM `$table_name`", 'X')));

		$t = $table_name;
		$arr = [
			'foo' => 1,
		];
		$result = $db->replace($t, $arr);
		$this->assertEquals(1, $result);

		$this->assertEquals(1, intval($db->queryOne("SELECT COUNT(*) AS X FROM `$table_name`", 'X')));

		$result = $db->replace($t, $arr);
		$this->assertEquals(2, $result);

		$this->assertEquals(1, intval($db->queryOne("SELECT COUNT(*) AS X FROM `$table_name`", 'X')));

		$arr = [
			'foo' => 2,
		];
		$result = $db->replace($t, $arr);
		$this->assertEquals(3, $result);

		$this->assertEquals(2, intval($db->queryOne("SELECT COUNT(*) AS X FROM `$table_name`", 'X')));

		//		$t = $table_name;
		//		$arr = [
		//			'foo' => 2,
		//		];
		$result = $db->replace($t, $arr);
		$this->assertEquals(4, $result);

		$this->assertEquals(2, intval($db->queryOne("SELECT COUNT(*) AS X FROM `$table_name`", 'X')));
	}

	public function test_selectOne(): void {
		$db = $this->application->databaseRegistry();

		$where = [];
		$order_by = '';
		$table = 'database_select_one_where';

		$this->prepareTestTable($table);
		$db->query("INSERT INTO $table (id, foo) VALUES (1, 2)");
		$result = $db->selectOne($table, $where, $order_by);
		$this->assertIsArray($result);
	}

	public function test_table_exists(): void {
		$db = $this->application->databaseRegistry();

		$this->assertFalse($db->tableExists(''));
		$this->assertFalse($db->tableExists('random_table_which_should_not_exist'));
		$table = StringTools::right(__METHOD__, '::');
		$db->query("DROP TABLE IF EXISTS $table");
		$this->assertFalse($db->tableExists($table));
		$db->query("CREATE TABLE $table ( id int )");
		$this->assertTrue($db->tableExists($table));
		$db->query("DROP TABLE IF EXISTS $table");
		$this->assertFalse($db->tableExists($table));
	}

	public function test_unstring(): void {
		$db = $this->application->databaseRegistry();

		$sql = "UPDATE `Metric` SET
	`Model` = '1',
	`CodeName` = 'hypoglycemia',
	`Name` = 'Side Effect 2: Hypoglycemia',
	`Label` = 'Side Effect 2: Hypoglycemia',
	`Subhead` = '',
	`Metric_Category` = '1',
	`AutoFormula` = 'true',
	`Formula` = '{hypoglycemia}\n(value < 0) ? (value * {significance-ratings}) : value',
	`Benefit_Min` = '-25',
	`Benefit_Max` = '0',
	`Scaling` = '1.7',
	`OrderIndex` = '4',
	`IsActive` = 'true',
	Modified=UTC_TIMESTAMP()	 WHERE `ID` = '4';";
		$state = null;
		//echo "OLD: $sql\n";
		$sql = Database::removeStringTokens($sql, $state);
		$sql = strtr($sql, [
			'\'\'' => 'empty-string',
		]);
		//echo "NEW: $sql\n";
		$this->assertStringNotContainsString('\'', $sql);

		$state = null;
		$this->assertEquals(Database::replaceStringTokens(Database::removeStringTokens($sql, $state), $state), $sql);
		$sql = "UPDATE `Metric` SET
	`Model` = '1',
	`CodeName` = 'hypog\\'lycemia',
	`Name` = 'Side Effect 2: Hypoglycemia',
	`Label` = 'Side Effect 2: Hypoglycemia',
	`Subhead` = '',
	`Metric_Category` = '1',
	`AutoFormula` = 'true',
	`Formula` = '{hypoglycemia}\n(value < 0) ? (value * {significance-ratings}) : value',
	`Benefit_Min` = '-25',
	`Benefit_Max` = '0',
	`Scaling` = '1.7',
	`OrderIndex` = '4',
	`IsActive` = 'true',
	Modified=UTC_TIMESTAMP()	 WHERE `ID` = '4';";
		$state = null;
		//echo "OLD: $sql\n";
		$new_sql = Database::removeStringTokens($sql, $state);
		//echo "NEW: $new_sql\n";
		$this->assertStringNotContainsString('\'', $new_sql);
		$state = null;
		$this->assertEquals(Database::replaceStringTokens(Database::removeStringTokens($sql, $state), $state), $sql);
	}

	public function test_update(): void {
		$db = $this->application->databaseRegistry();

		$table = 'database_update_test';

		$this->prepareTestTable($table, null, false);

		$db->insert($table, [
			'foo' => 1,
		]);
		$db->insert($table, [
			'foo' => 2,
		]);
		$db->insert($table, [
			'foo' => 2,
		]);
		$db->insert($table, [
			'foo' => 2,
		]);
		$db->insert($table, [
			'foo' => 3,
		]);
		$db->insert($table, [
			'foo' => 3,
		]);
		$db->insert($table, [
			'foo' => 3,
		]);
		$db->insert($table, [
			'foo' => 3,
		]);
		$db->insert($table, [
			'foo' => 3,
		]);
		$db->insert($table, [
			'foo' => 3,
		]);
		$db->insert($table, [
			'foo' => 3,
		]);

		$options = [];

		$tests = [
			[
				[
					'foo' => '99',
				], [
					'foo' => 1,
				], 1,
			], [
				[
					'foo' => '99',
				], [
					'foo' => 2,
				], 3,
			], [
				[
					'foo' => '99',
				], [
					'foo' => 3,
				], 7,
			], [
				[
					'foo' => 0,
				], [
					'foo' => 99,
				], 11,
			],
		];

		foreach ($tests as $test) {
			[$what, $where, $count] = $test;
			$result = $db->update($table, $what, $where, $options);
			$this->assertInstanceOf(QueryResult::class, $result, "\$db->update($table, " . PHP::dump($what) . ', ' . PHP::dump($where) . ', ' . PHP::dump($options) . ') === true');
			$this->assertEquals($count, $db->affectedRows($result));
		}
	}

	public function test_update1(): void {
		$db = $this->application->databaseRegistry();

		$table = 'database_update_test';

		$this->prepareTestTable($table);

		$id = $db->insert($table, [
			'foo' => 6,
		]);

		$row = $db->selectOne($table, [
			'id' => $id,
		]);

		$this->assertEquals(6, $row['foo']);

		$arr = [
			'foo' => 100,
		];
		$db->update($table, $arr, [
			'foo' => 6,
		]);

		$row = $db->selectOne($table, [
			'id' => $id,
		]);

		$this->assertEquals(100, $row['foo']);
	}

	public function test_update_id_test(): void {
		$db = $this->application->databaseRegistry();
		$table = 'db_update_id_test';

		$this->prepareTestTable($table);

		$id = $db->insert($table, [
			'foo' => 6,
		]);

		$row = $db->selectOne($table, [
			'id' => $id,
		]);

		$this->assertEquals(6, $row['foo']);

		$arr = [
			'foo' => 100,
		];
		$db->update($table, $arr, [
			'id' => $id,
		]);

		$row = $db->selectOne($table, [
			'id' => $id,
		]);

		$this->assertEquals(100, $row['foo']);
	}

	public function test_url(): void {
		$db = $this->application->databaseRegistry();
		$this->assertIsString($db->url());
		$this->assertIsString($db->urlComponent('user'));
		$this->assertIsString($db->urlComponent('scheme'));
		$this->assertIsString($db->urlComponent('name'));
		$this->assertIsString($db->urlComponent('host'));
		$this->assertIsString($db->urlComponent('pass'));
	}
}
