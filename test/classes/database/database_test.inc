<?php

namespace zesk;

class Database_Test extends Test_Unit {
	// 	function test_table_indexes() {
	// 		$table = "db_table_indexes_test";

	// 		$db = $this->application->database_factory();
	// 		$this->test_table($table);

	// 		$dbname = "";
	// 		$result = $db->table_indexes($table, $dbname);

	// 		dump($result);

	// 		$this->assert_arrays_equal(array(
	// 			"ID"
	// 		), avalue($result, "PRIMARY"));
	// 		$this->assert_arrays_equal(array(
	// 			"Foo"
	// 		), avalue($result, "f"));
	// 	}
	function test_table_columns() {
		$table = "db_table_indexes_test";
		$db = $this->application->database_factory();

		$this->test_table($table);

		$dbname = "";
		$result = $db->table_column($table);

		$compare_table = new Database_Table($db, $table);
		$compare_result = array(
			"ID" => new Database_Column($compare_table, "ID", array(
				"sql_type" => "int(11) unsigned",
				"not null" => true,
				"serial" => true
			)),
			"Foo" => new Database_Column($compare_table, "Foo", array(
				"sql_type" => "int(11)",
				"not null" => true,
				"serial" => false
			))
		);

		$this->assert_arrays_equal($result, $compare_result);
	}
	public static $test = null;
	function test_query_object() {
		$db = $this->application->database_factory();
		$table_name = "query_object_test";
		$this->test_table($table_name);

		$db->insert($table_name, array(
			"Foo" => 100
		));
		$db->insert($table_name, array(
			"Foo" => 200
		));
		$db->insert($table_name, array(
			"Foo" => 300
		));

		$sql = "SELECT * FROM $table_name";
		$k = "ID";
		$phpClass = "DBQueryObjectTest";
		$options = false;
		$dbname = "";

		self::$test = $this;

		$result = $db->query_array($sql, $k, null);
		foreach ($result as $k => $v) {
			$result[$k] = new DBQueryObjectTest($v);
		}

		$compare_result[1] = new DBQueryObjectTest(array(
			"ID" => 1,
			"Foo" => 100
		));
		$compare_result[2] = new DBQueryObjectTest(array(
			"ID" => 2,
			"Foo" => 200
		));
		$compare_result[3] = new DBQueryObjectTest(array(
			"ID" => 3,
			"Foo" => 300
		));

		$this->assert_arrays_equal($result, $compare_result);
		self::$test = null;
	}
	function test_query_array_index() {
		$db = $this->application->database_factory();
		$table = "query_array_index_test";

		$this->test_table($table);

		$sql = "SELECT * FROM $table ORDER BY Foo ASC";
		$k = 0;
		$v = 1;
		$default = null;
		$dbname = "";
		$result = $db->query_array_index($sql, $k, $v, $default, $dbname);

		$this->assert_arrays_equal($result, array());

		$db->insert($table, array(
			"Foo" => 10
		));
		$db->insert($table, array(
			"Foo" => 20
		));
		$db->insert($table, array(
			"Foo" => 30
		));

		$result = $db->query_array_index($sql);
		$this->assert_arrays_equal($result, array(
			array(
				0 => "1",
				1 => "10"
			),
			array(
				0 => "2",
				1 => "20"
			),
			array(
				0 => "3",
				1 => "30"
			)
		));

		$result = $db->query_array_index($sql, $k, null, $default, $dbname);
		$this->assert_arrays_equal($result, array(
			"1" => array(
				0 => "1",
				1 => "10"
			),
			"2" => array(
				0 => "2",
				1 => "20"
			),
			"3" => array(
				0 => "3",
				1 => "30"
			)
		));

		$result = $db->query_array_index($sql, false, $v, $default, $dbname);

		$this->assert_arrays_equal($result, array(
			"10",
			"20",
			"30"
		));
	}
	function test_query_array() {
		$db = $this->application->database_factory();

		$table = "query_array_test";

		$this->test_table($table);

		$sql = "SELECT * FROM $table ORDER BY Foo ASC";
		$k = "ID";
		$v = "Foo";
		$default = null;
		$dbname = "";
		$result = $db->query_array($sql, $k, $v, $default, $dbname);

		$this->assert_arrays_equal($result, array());

		$db->insert($table, array(
			"Foo" => 10
		));
		$db->insert($table, array(
			"Foo" => 20
		));
		$db->insert($table, array(
			"Foo" => 30
		));

		$result = $db->query_array($sql);
		$this->assert_arrays_equal($result, array(
			array(
				"ID" => "1",
				"Foo" => "10"
			),
			array(
				"ID" => "2",
				"Foo" => "20"
			),
			array(
				"ID" => "3",
				"Foo" => "30"
			)
		));

		$result = $db->query_array($sql, $k, false, $default, $dbname);
		$this->assert_arrays_equal($result, array(
			"1" => array(
				"ID" => "1",
				"Foo" => "10"
			),
			"2" => array(
				"ID" => "2",
				"Foo" => "20"
			),
			"3" => array(
				"ID" => "3",
				"Foo" => "30"
			)
		));

		$result = $db->query_array($sql, false, "Foo", $default, $dbname);

		$this->assert_arrays_equal($result, array(
			"10",
			"20",
			"30"
		));

		$result = $db->query_array($sql, null, "Foo", $default, $dbname);

		$this->assert_arrays_equal($result, array(
			"10",
			"20",
			"30"
		));

		$result = $db->query_array($sql, "Foo", "ID", $default, $dbname);

		$this->assert_arrays_equal($result, array(
			"10" => "1",
			"20" => "2",
			"30" => "3"
		));

		$result = $db->query_array($sql, "Foo", null, $default, $dbname);

		$this->assert_arrays_equal($result, array(
			"10" => array(
				"ID" => "1",
				"Foo" => "10"
			),
			"20" => array(
				"ID" => "2",
				"Foo" => "20"
			),
			"30" => array(
				"ID" => "3",
				"Foo" => "30"
			)
		));
	}
	function test_configured() {
		$db = $this->application->database_factory();
		$db->configured();
	}
	function test_affected_rows() {
		$db = $this->application->database_factory();

		$db->affected_rows();
		echo basename(__FILE__) . ": success\n";
	}
	function test_auto_table_names() {
		$db = $this->application->database_factory();

		$set = null;
		$db->auto_table_names($set);
		echo basename(__FILE__) . ": success\n";
	}
	function test_auto_table_names_options() {
		$db = $this->application->database_factory();

		$set = null;
		$db->auto_table_names_options($set);
		echo basename(__FILE__) . ": success\n";
	}
	function test_auto_table_names_replace() {
		$db = $this->application->database_factory();

		$sql = null;
		$db->auto_table_names_replace($sql);
		echo basename(__FILE__) . ": success\n";
	}
	function test_bytes_used() {
		$db = $this->application->database_factory();

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

		echo basename(__FILE__) . ": success\n";
	}
	function test_connect() {
		$db = $this->application->database_factory();

		$name = "";
		$url = false;
		$db->connect($name, $url);
		echo basename(__FILE__) . ": success\n";
	}
	function test_reconnect() {
		$db = $this->application->database_factory();
		$db->reconnect();
		echo basename(__FILE__) . ": success\n";
	}
	function test_query_one() {
		$db = $this->application->database_factory();

		$table = "select_map_test";

		$this->test_table($table);

		$where = array(
			"Foo" => 2
		);
		$db->insert($table, $where);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM $table", "X", 100)) === 1);

		$where = array(
			"Foo" => 3
		);
		$db->insert($table, $where);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM $table", "X", 100)) === 2);

		$dbname = "";
		$db->delete($table, $where, $dbname);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM $table", "X", 100)) === 1);

		echo basename(__FILE__) . ": success\n";
	}
	function test_dump() {
		$db = $this->application->database_factory();

		$db->dump(file::temporary(".dbdump"));
	}
	function test_fetch_array() {
		$db = $this->application->database_factory();

		$res = $db->query("SELECT 1 AS A,2 AS B,3 as C,4 AS D,5 as E,'string' AS F");
		$db->fetch_array($res);
	}
	function test_get_lock() {
		$db = $this->application->database_factory();

		$name = md5(microtime());
		$wait_seconds = 0;
		$result = $db->get_lock("test", $wait_seconds);
		$this->assert_true($result, "get_lock(\"$name\") did not return true: " . _dump($result));
		$result = $db->release_lock($name);
		$this->assert_true($result, "release_lock(\"$name\") did not return true: " . _dump($result));
	}
	function test_insert() {
		$db = $this->application->database_factory();

		$table_name = "insert_test";

		$this->test_table($table_name);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 0);

		$t = $table_name;
		$arr = array(
			"Foo" => 1
		);
		$db->insert($t, $arr);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 1);

		$t = $table_name;
		$arr = array(
			"Foo" => 2
		);
		$db->insert($t, $arr);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 2);

		$caught = false;
		try {
			$t = $table_name;
			$arr = array(
				"Foo" => 2
			);
			$dbname = "";
			$result = $db->insert($t, $arr);
		} catch (Database_Exception_Duplicate $e) {
			$caught = true;
		}
		$this->assert($caught === true);

		echo basename(__FILE__) . ": success\n";
	}
	function test_insert_id() {
		$db = $this->application->database_factory();

		$db->insert_id();
		echo basename(__FILE__) . ": success\n";
	}
	function test_query() {
		$db = $this->application->database_factory();

		$table_name = "query_test";

		$this->test_table($table_name);

		$sql = "SELECT * FROM $table_name";
		$do_die = true;
		$result = $db->query($sql);
		$this->log("\$db->query result is " . gettype($result));
		$this->assert(is_object($result));

		$sql = "UPDATE $table_name SET Foo=Foo+1";
		$this->assert(is_bool($db->query($sql)));

		echo basename(__FILE__) . ": success\n";
	}
	function test_query_integer() {
		$db = $this->application->database_factory();

		$sql = "SELECT 23 * 54 AS X";
		$field = "X";
		$default = 0;
		$this->assert($db->query_integer($sql, "X", $default) === 23 * 54);
		$this->assert($db->query_integer($sql, "Y", $default) === 0);
		$this->assert($db->query_integer($sql, "Y", null) === null);
		echo basename(__FILE__) . ": success\n";
	}
	function test_query_one1() {
		$db = $this->application->database_factory();

		$table_name = "query_object_test";

		$this->test_table($table_name);

		$n = mt_rand(100, 1000);
		for ($i = 1; $i <= $n; $i++) {
			$db->insert($table_name, array(
				"Foo" => $i * 100
			));
		}
		$sql = "SELECT MAX(ID) AS X FROM $table_name";
		$this->assert(intval($db->query_one($sql, "X", -1)) === $n);
		$sql = "SELECT MIN(ID) AS X FROM $table_name";
		$this->assert(intval($db->query_one($sql, "X", -1)) === 1);
		$sql = "SELECT MAX(Foo) AS X FROM $table_name";
		$this->assert(intval($db->query_one($sql, "X", -1)) === $n * 100);
		$sql = "SELECT MIN(Foo) AS X FROM $table_name";
		$this->assert(intval($db->query_one($sql, "X", -1)) === 100);

		echo basename(__FILE__) . ": success\n";
	}
	function test_register() {
		$db = $this->application->database_factory();

		$name = null;
		$url = null;
		$is_default = false;
		$db->register($name, $url, $is_default);
		echo basename(__FILE__) . ": success\n";
	}

	/**
	 * @always_fail
	 */
	function test_release_lock() {
		$db = $this->application->database_factory();
		$name = "fail";
		$result = $db->release_lock($name);
		$this->assert_false($result);
	}
	function test_locks() {
		$db = $this->application->database_factory();

		$name = __FUNCTION__;
		$this->assert($db->release_lock($name) === false);
		$this->assert($db->get_lock($name) === true);
		$this->assert($db->get_lock($name) === true);
		$this->assert($db->release_lock($name) === true);
		$this->assert($db->release_lock($name) === false);

		$this->assert($db->get_lock($name) === true);

		echo basename(__FILE__) . ": success\n";
	}
	function test_replace() {
		$db = $this->application->database_factory();

		$table_name = "replace_test";

		$this->test_table($table_name);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 0);

		$t = $table_name;
		$arr = array(
			"Foo" => 1
		);
		$result = $db->replace($t, $arr);
		$this->assert($result === 1);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 1);

		$result = $db->replace($t, $arr);
		$this->assert($result === 2);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 1);

		$arr = array(
			"Foo" => 2
		);
		$result = $db->replace($t, $arr);
		$this->assert($result === 3);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 2);

		$t = $table_name;
		$arr = array(
			"Foo" => 2
		);
		$result = $db->replace($t, $arr);
		$this->assert($result === 4);

		$this->assert(intval($db->query_one("SELECT COUNT(*) AS X FROM `$table_name`", "X", 100)) === 2);
	}
	function test_select_one_where() {
		$db = $this->application->database_factory();

		$where = null;
		$order_by = false;
		$table = "database_select_one_where";

		$this->test_table($table);

		$db->select_one_where($table, $where, $order_by);
	}
	function test_table_exists() {
		$db = $this->application->database_factory();

		$table = null;
		$db->table_exists($table);
	}
	function test_unstring() {
		$db = $this->application->database_factory();

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
		echo "OLD: $sql\n";
		$sql = Database::unstring($sql, $state);
		$sql = strtr($sql, array(
			"''" => "empty-string"
		));
		echo "NEW: $sql\n";
		$this->assert(strpos($sql, "'") === false);

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
		echo "OLD: $sql\n";
		$new_sql = Database::unstring($sql, $state);
		echo "NEW: $new_sql\n";
		$this->assert(strpos($new_sql, "'") === false);
		$state = null;
		$this->assert_equal(Database::restring(Database::unstring($sql, $state), $state), $sql);
	}
	function test_update() {
		$db = $this->application->database_factory();

		$table = "database_update_test";

		$this->test_table($table, null, false);

		$db->insert($table, array(
			"Foo" => 1
		));
		$db->insert($table, array(
			"Foo" => 2
		));
		$db->insert($table, array(
			"Foo" => 2
		));
		$db->insert($table, array(
			"Foo" => 2
		));
		$db->insert($table, array(
			"Foo" => 3
		));
		$db->insert($table, array(
			"Foo" => 3
		));
		$db->insert($table, array(
			"Foo" => 3
		));
		$db->insert($table, array(
			"Foo" => 3
		));
		$db->insert($table, array(
			"Foo" => 3
		));
		$db->insert($table, array(
			"Foo" => 3
		));
		$db->insert($table, array(
			"Foo" => 3
		));

		$options = array();

		$tests = array(
			array(
				array(
					"Foo" => "99"
				),
				array(
					"Foo" => 1
				),
				1
			),
			array(
				array(
					"Foo" => "99"
				),
				array(
					"Foo" => 2
				),
				3
			),
			array(
				array(
					"Foo" => "99"
				),
				array(
					"Foo" => 3
				),
				7
			),
			array(
				array(
					"Foo" => 0
				),
				array(
					"Foo" => 99
				),
				11
			)
		);

		foreach ($tests as $test) {
			list($what, $where, $count) = $test;
			$this->assert($db->update($table, $what, $where, $options) === true, "\$db->update($table, " . PHP::dump($what) . ", " . PHP::dump($where) . ", " . PHP::dump($options) . ") === true");
			$this->assert($db->affected_rows() === $count);
		}
	}
	function test_update1() {
		$db = $this->application->database_factory();

		$table = "database_update_test";

		$this->test_table($table);

		$id = $db->insert($table, array(
			"Foo" => 6
		));

		$row = $db->select_one_where($table, array(
			"ID" => $id
		));

		$this->assert($row['Foo'] == 6);

		$arr = array(
			"Foo" => 100
		);
		$idname = "ID";
		$low_priority = false;
		$dbname = '';
		$db->update($table, $arr, array(
			"Foo" => 6
		));

		$row = $db->select_one_where($table, array(
			"ID" => $id
		));

		$this->assert($row['Foo'] == 100);
	}
	function test_update_id_test() {
		$db = $this->application->database_factory();
		$table = "db_update_id_test";

		$this->test_table($table);

		$id = $db->insert($table, array(
			"Foo" => 6
		));

		$row = $db->select_one_where($table, array(
			"ID" => $id
		));

		$this->assert($row['Foo'] == 6);

		$arr = array(
			"Foo" => 100
		);
		$db->update($table, $arr, array(
			"ID" => $id
		));

		$row = $db->select_one_where($table, array(
			"ID" => $id
		));

		$this->assert($row['Foo'] == 100);
	}
	function test_url() {
		$db = $this->application->database_factory();
		$name = '';
		$url = null;
		$is_default = false;
		$db->url($name, $url, $is_default);
	}
}
class DBQueryObjectTest extends Object {
	protected $columns = array(
		"ID",
		"Foo"
	);
	function validate() {
		$test = Database_Test::$test;
		$test->assert(!$this->member_is_empty("ID"));
		$test->assert(!$this->member_is_empty("Foo"));
	}
}
