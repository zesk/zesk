<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Test extends Test_Unit {
	protected array $load_modules = [
		"MySQL",
		"ORM",
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Test::initialize()
	 */
	public function initialize(): void {
		require __DIR__ . "/Database_Test_Objects.php";
	}

	// 	function test_table_indexes() {
	// 		$table = "db_table_indexes_test";

	// 		$db = $this->application->database_registry();
	// 		$this->test_table($table);

	// 		$dbname = "";
	// 		$result = $db->table_indexes($table, $dbname);

	// 		dump($result);

	// 		$this->assert_arrays_equal(array(
	// 			"id"
	// 		), avalue($result, "PRIMARY"));
	// 		$this->assert_arrays_equal(array(
	// 			"foo"
	// 		), avalue($result, "f"));
	// 	}
	public function test_table_columns(): void {
		$table = "db_table_indexes_test";
		$db = $this->application->database_registry();

		$this->test_table($table);

		$dbname = "";
		$result = $db->table_column($table);

		$compare_table = new Database_Table($db, $table);
		$compare_result = [
			"id" => new Database_Column($compare_table, "id", [
				"sql_type" => "int(11) unsigned",
				"not null" => true,
				"serial" => true,
			]),
			"foo" => new Database_Column($compare_table, "foo", [
				"sql_type" => "int(11)",
				"not null" => true,
				"serial" => false,
			]),
		];

		$this->assert_arrays_equal($result, $compare_result);
	}

	public static $test = null;

	public function test_query_object(): void {
		$db = $this->application->database_registry();
		$table_name = "query_object_test";
		$this->test_table($table_name);

		$db->insert($table_name, [
			"foo" => 100,
		]);
		$db->insert($table_name, [
			"foo" => 200,
		]);
		$db->insert($table_name, [
			"foo" => 300,
		]);

		$sql = "SELECT * FROM $table_name";
		$k = "id";
		$phpClass = "DBQueryObjectTest";
		$options = false;
		$dbname = "";

		self::$test = $this;

		$result = $db->query_array($sql, $k, null);
		foreach ($result as $k => $v) {
			$result[$k] = new DBQueryObjectTest($this->application, $v);
		}

		$compare_result[1] = new DBQueryObjectTest($this->application, [
			"id" => 1,
			"foo" => 100,
		]);
		$compare_result[2] = new DBQueryObjectTest($this->application, [
			"id" => 2,
			"foo" => 200,
		]);
		$compare_result[3] = new DBQueryObjectTest($this->application, [
			"id" => 3,
			"foo" => 300,
		]);

		$this->assert_arrays_equal($result, $compare_result);
		self::$test = null;
	}

	public function test_query_array_index(): void {
		$db = $this->application->database_registry();
		$table = "query_array_index_test";

		$this->test_table($table);

		$sql = "SELECT * FROM $table ORDER BY Foo ASC";
		$k = 0;
		$v = 1;
		$default = null;
		$dbname = "";
		$result = $db->query_array_index($sql, $k, $v, $default, $dbname);

		$this->assert_arrays_equal($result, []);

		$db->insert($table, [
			"foo" => 10,
		]);
		$db->insert($table, [
			"foo" => 20,
		]);
		$db->insert($table, [
			"foo" => 30,
		]);

		$result = $db->query_array_index($sql);
		$this->assert_arrays_equal($result, [
			[
				0 => "1",
				1 => "10",
			],
			[
				0 => "2",
				1 => "20",
			],
			[
				0 => "3",
				1 => "30",
			],
		]);

		$result = $db->query_array_index($sql, $k, null, $default, $dbname);
		$this->assert_arrays_equal($result, [
			"1" => [
				0 => "1",
				1 => "10",
			],
			"2" => [
				0 => "2",
				1 => "20",
			],
			"3" => [
				0 => "3",
				1 => "30",
			],
		]);

		$result = $db->query_array_index($sql, false, $v, $default, $dbname);

		$this->assert_arrays_equal($result, [
			"10",
			"20",
			"30",
		]);
	}

	public function test_query_array(): void {
		$db = $this->application->database_registry();

		$table = "query_array_test";

		$this->test_table($table);

		$sql = "SELECT * FROM $table ORDER BY Foo ASC";
		$k = "id";
		$v = "foo";
		$default = null;
		$dbname = "";
		$result = $db->query_array($sql, $k, $v, $default, $dbname);

		$this->assert_arrays_equal($result, []);

		$db->insert($table, [
			"foo" => 10,
		]);
		$db->insert($table, [
			"foo" => 20,
		]);
		$db->insert($table, [
			"foo" => 30,
		]);

		$result = $db->query_array($sql);
		$this->assert_arrays_equal($result, [
			[
				"id" => "1",
				"foo" => "10",
			],
			[
				"id" => "2",
				"foo" => "20",
			],
			[
				"id" => "3",
				"foo" => "30",
			],
		]);

		$result = $db->query_array($sql, $k, false, $default, $dbname);
		$this->assert_arrays_equal($result, [
			"1" => [
				"id" => "1",
				"foo" => "10",
			],
			"2" => [
				"id" => "2",
				"foo" => "20",
			],
			"3" => [
				"id" => "3",
				"foo" => "30",
			],
		]);

		$result = $db->query_array($sql, false, "foo", $default, $dbname);

		$this->assert_arrays_equal($result, [
			"10",
			"20",
			"30",
		]);

		$result = $db->query_array($sql, null, "foo", $default, $dbname);

		$this->assert_arrays_equal($result, [
			"10",
			"20",
			"30",
		]);

		$result = $db->query_array($sql, "foo", "id", $default, $dbname);

		$this->assert_arrays_equal($result, [
			"10" => "1",
			"20" => "2",
			"30" => "3",
		]);

		$result = $db->query_array($sql, "foo", null, $default, $dbname);

		$this->assert_arrays_equal($result, [
			"10" => [
				"id" => "1",
				"foo" => "10",
			],
			"20" => [
				"id" => "2",
				"foo" => "20",
			],
			"30" => [
				"id" => "3",
				"foo" => "30",
			],
		]);
	}

	public function test_affected_rows(): void {
		$db = $this->application->database_registry();

		$db->affected_rows();
	}

	public function test_auto_table_names(): void {
		$db = $this->application->database_registry();

		$set = null;
		$db->auto_table_names($set);
	}

	public function test_auto_table_names_options(): void {
		$db = $this->application->database_registry();

		$set = null;
		$db->auto_table_names_options($set);
	}

	public function test_auto_table_names_replace(): void {
		$db = $this->application->database_registry();

		$sql = null;
		$db->auto_table_names_replace($sql);
	}

	public function test_bytes_used(): void {
		$db = $this->application->database_registry();

		//echo DB_URL ."\n";
		//$db->debug();
		$table = null;
		$default = null;
		$db->bytes_used($table, $default);

		$table = $this->test_table("bytes_used");
		$status = $db->query_one("SHOW TABLE STATUS LIKE 'bytes_used'");

		Debug::output($status);

		$this->assert(array_key_exists("Name", $status));
		$this->assert(array_key_exists("Rows", $status));
		$this->assert(array_key_exists("Data_length", $status));
		$this->assert(array_key_exists("Index_length", $status));
	}

	public function test_connect(): void {
		$db = $this->application->database_registry();

		$name = "";
		$url = false;
		$db->connect($name, $url);
	}

	public function test_reconnect(): void {
		$db = $this->application->database_registry();
		$db->reconnect();
	}

	public function test_query_one(): void {
		$db = $this->application->database_registry();

		$table = "select_map_test";

		$this->test_table($table);

		$where = [
			"foo" => 2,
		];
		$db->insert($table, $where);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM $table", "X", 100)) === 1);

		$where = [
			"foo" => 3,
		];
		$db->insert($table, $where);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM $table", "X", 100)) === 2);

		$dbname = "";
		$db->delete($table, $where, $dbname);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM $table", "X", 100)) === 1);
	}

	public function test_dump(): void {
		$db = $this->application->database_registry();

		$db->dump(File::temporary($this->sandbox(), ".dbdump"));
	}

	public function test_fetch_array(): void {
		$db = $this->application->database_registry();

		$res = $db->query("SELECT 1 AS A,2 AS B,3 as C,4 AS D,5 as E,'string' AS F");
		$db->fetch_array($res);
	}

	public function test_get_lock(): void {
		$db = $this->application->database_registry();

		$name = md5(microtime());
		$wait_seconds = 0;
		$result = $db->get_lock($name, $wait_seconds);
		$this->assert_true($result, "get_lock(\"$name\") did not return true: " . _dump($result));
		$result = $db->release_lock($name);
		$this->assert_true($result, "release_lock(\"$name\") did not return true: " . _dump($result));
	}

	public function test_insert(): void {
		$db = $this->application->database_registry();

		$table_name = "insert_test";

		$this->test_table($table_name);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 0);

		$t = $table_name;
		$arr = [
			"foo" => 1,
		];
		$db->insert($t, $arr);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 1);

		$t = $table_name;
		$arr = [
			"foo" => 2,
		];
		$db->insert($t, $arr);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 2);

		$caught = false;

		try {
			$t = $table_name;
			$arr = [
				"foo" => 2,
			];
			$dbname = "";
			$result = $db->insert($t, $arr);
		} catch (Database_Exception_Duplicate $e) {
			$caught = true;
		}
		$this->assert($caught === true);
	}

	public function test_insert_id(): void {
		$db = $this->application->database_registry();

		$db->insert_id();
	}

	public function test_query(): void {
		$db = $this->application->database_registry();

		$table_name = "query_test";

		$this->test_table($table_name);

		$sql = "SELECT * FROM $table_name";
		$do_die = true;
		$result = $db->query($sql);
		$this->log("\$db->query result is " . gettype($result));
		$this->assert(is_object($result));

		$sql = "UPDATE $table_name SET Foo=Foo+1";
		$this->assert(is_bool($db->query($sql)));
	}

	public function test_query_integer(): void {
		$db = $this->application->database_registry();

		$sql = "SELECT 23 * 54 AS X";
		$field = "X";
		$default = 0;
		$this->assert($db->query_integer($sql, "X", $default) === 23 * 54);
		$this->assert($db->query_integer($sql, "Y", $default) === 0);
		$this->assert($db->query_integer($sql, "Y", null) === null);
	}

	public function test_query_one1(): void {
		$db = $this->application->database_registry();

		$table_name = "query_object_test";

		$this->test_table($table_name);

		$n = random_int(100, 1000);
		for ($i = 1; $i <= $n; $i++) {
			$db->insert($table_name, [
				"foo" => $i * 100,
			]);
		}
		$sql = "SELECT MAX(ID) AS X FROM $table_name";
		$this->assert(intval($db->query_one($sql, "X", -1)) === $n);
		$sql = "SELECT MIN(ID) AS X FROM $table_name";
		$this->assert(intval($db->query_one($sql, "X", -1)) === 1);
		$sql = "SELECT MAX(Foo) AS X FROM $table_name";
		$this->assert(intval($db->query_one($sql, "X", -1)) === $n * 100);
		$sql = "SELECT MIN(Foo) AS X FROM $table_name";
		$this->assert(intval($db->query_one($sql, "X", -1)) === 100);
	}

	/**
	 * @always_fail
	 */
	public function test_release_lock(): void {
		$db = $this->application->database_registry();
		$name = "fail";
		$result = $db->release_lock($name);
		$this->assert_false($result);
	}

	public function test_locks(): void {
		$db = $this->application->database_registry();

		$name = __FUNCTION__;
		$this->assert_false($db->release_lock($name));
		$this->assert_true($db->get_lock($name));
		$this->assert_true($db->get_lock($name));
		$this->assert_true($db->release_lock($name));

		$intversion = 0;
		if ($db instanceof \MySQL\Database) {
			$version = $db->version;
			[$maj, $min, $patch, $rest] = explode(".", $version, 4) + array_fill(0, 4, 0);
			$intversion = intval($maj) * 10000 + intval($min) * 100 + intval($patch);
		}
		if ($intversion >= 50700) {
			$this->assert_true($db->release_lock($name));
			$this->assert_false($db->release_lock($name));
			$this->assert_false($db->release_lock($name));
		} else {
			$this->assert_false($db->release_lock($name));
			$this->assert_false($db->release_lock($name));
			$this->assert_false($db->release_lock($name));
		}

		$this->assert($db->get_lock($name) === true);
	}

	public function test_replace(): void {
		$db = $this->application->database_registry();

		$table_name = "replace_test";

		$this->test_table($table_name);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 0);

		$t = $table_name;
		$arr = [
			"foo" => 1,
		];
		$result = $db->replace($t, $arr);
		$this->assert($result === 1);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 1);

		$result = $db->replace($t, $arr);
		$this->assert($result === 2);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 1);

		$arr = [
			"foo" => 2,
		];
		$result = $db->replace($t, $arr);
		$this->assert($result === 3);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 2);

		$t = $table_name;
		$arr = [
			"foo" => 2,
		];
		$result = $db->replace($t, $arr);
		$this->assert($result === 4);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 2);
	}

	public function test_select_one_where(): void {
		$db = $this->application->database_registry();

		$where = null;
		$order_by = false;
		$table = "database_select_one_where";

		$this->test_table($table);

		$db->select_one_where($table, $where, $order_by);
	}

	public function test_table_exists(): void {
		$db = $this->application->database_registry();

		$table = null;
		$db->table_exists($table);
	}

	public function test_unstring(): void {
		$db = $this->application->database_registry();

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
		$sql = Database::unstring($sql, $state);
		$sql = strtr($sql, [
			"''" => "empty-string",
		]);
		//echo "NEW: $sql\n";
		$this->assert(!str_contains($sql, "'"));

		$state = null;
		$this->assert_equal(Database::restring(Database::unstring($sql, $state), $state), $sql);
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
		$new_sql = Database::unstring($sql, $state);
		//echo "NEW: $new_sql\n";
		$this->assert(!str_contains($new_sql, "'"));
		$state = null;
		$this->assert_equal(Database::restring(Database::unstring($sql, $state), $state), $sql);
	}

	public function test_update(): void {
		$db = $this->application->database_registry();

		$table = "database_update_test";

		$this->test_table($table, null, false);

		$db->insert($table, [
			"foo" => 1,
		]);
		$db->insert($table, [
			"foo" => 2,
		]);
		$db->insert($table, [
			"foo" => 2,
		]);
		$db->insert($table, [
			"foo" => 2,
		]);
		$db->insert($table, [
			"foo" => 3,
		]);
		$db->insert($table, [
			"foo" => 3,
		]);
		$db->insert($table, [
			"foo" => 3,
		]);
		$db->insert($table, [
			"foo" => 3,
		]);
		$db->insert($table, [
			"foo" => 3,
		]);
		$db->insert($table, [
			"foo" => 3,
		]);
		$db->insert($table, [
			"foo" => 3,
		]);

		$options = [];

		$tests = [
			[
				[
					"foo" => "99",
				],
				[
					"foo" => 1,
				],
				1,
			],
			[
				[
					"foo" => "99",
				],
				[
					"foo" => 2,
				],
				3,
			],
			[
				[
					"foo" => "99",
				],
				[
					"foo" => 3,
				],
				7,
			],
			[
				[
					"foo" => 0,
				],
				[
					"foo" => 99,
				],
				11,
			],
		];

		foreach ($tests as $test) {
			[$what, $where, $count] = $test;
			$this->assert($db->update($table, $what, $where, $options) === true, "\$db->update($table, " . PHP::dump($what) . ", " . PHP::dump($where) . ", " . PHP::dump($options) . ") === true");
			$this->assert($db->affected_rows() === $count);
		}
	}

	public function test_update1(): void {
		$db = $this->application->database_registry();

		$table = "database_update_test";

		$this->test_table($table);

		$id = $db->insert($table, [
			"foo" => 6,
		]);

		$row = $db->select_one_where($table, [
			"id" => $id,
		]);

		$this->assert($row["foo"] == 6);

		$arr = [
			"foo" => 100,
		];
		$idname = "id";
		$low_priority = false;
		$dbname = '';
		$db->update($table, $arr, [
			"foo" => 6,
		]);

		$row = $db->select_one_where($table, [
			"id" => $id,
		]);

		$this->assert($row["foo"] == 100);
	}

	public function test_update_id_test(): void {
		$db = $this->application->database_registry();
		$table = "db_update_id_test";

		$this->test_table($table);

		$id = $db->insert($table, [
			"foo" => 6,
		]);

		$row = $db->select_one_where($table, [
			"id" => $id,
		]);

		$this->assert($row["foo"] == 6);

		$arr = [
			"foo" => 100,
		];
		$db->update($table, $arr, [
			"id" => $id,
		]);

		$row = $db->select_one_where($table, [
			"id" => $id,
		]);

		$this->assert($row["foo"] == 100);
	}

	public function test_url(): void {
		$db = $this->application->database_registry();
		$name = '';
		$url = null;
		$is_default = false;
		$db->url($name, $url, $is_default);
	}
}
