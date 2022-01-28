<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Database_Query_Select_Test extends Test_Unit {
	protected array $load_modules = [
		"MySQL",
		"ORM",
	];

	public function initialize(): void {
		// $this->schema_synchronize(Person::class);
	}

	public function test_main(): void {
		$table_name = "Database_Query_Select";

		$this->test_table($table_name);

		$db = $this->application->database_registry();
		$testx = new Database_Query_Select($db);

		$testx->setWhatString("*");
		$testx->addWhat("X", "COUNT(ID)");
		$testx->addWhat("Y", "SUM(Total)");

		$alias = '';
		$testx->from($table_name, $alias);

		$sql = 'INNER JOIN Person P ON P.ID=U.Person';
		$join_id = "person";
		$testx->join($sql, $join_id);

		$k = null;
		$v = null;
		$testx->where($k, $v);

		$order_by = null;
		$testx->order_by($order_by);

		$testx->groupBy(["X"]);

		$offset = 0;
		$limit = null;
		$testx->limit($offset, $limit);

		$testx->__toString();

		$testx->iterator();

		$class = null;
		$testx->orm_iterator($class);


		// Hits database
		//
//		$testx->orm("User");
		//
		//		$field = "id";
		//		$default = null;
		//		$testx->one($field, $default);

//		$field = "field2";
//		$default = 0;
//		$testx->one_integer($field, $default);

//		$field = "field1";
//		$default = 0;
//		$testx->integer($field, $default);

//		$testx->to_array();

		$testx->database();

		$class = Server::class;
		$testx->setORMClass($class);

		$db = $this->application->database_registry();
		$x = new Database_Query_Select($db);
		$x->from($table_name);
		$x->setWhatString("ID");
		$x->where("ID", [
			1,
			2,
			3,
			4,
		]);

		$result = strval($x);
		$valid_result = "SELECT ID FROM `Database_Query_Select` WHERE (`ID` = 1 OR `ID` = 2 OR `ID` = 3 OR `ID` = 4)";

		$result = preg_replace('/\s+/', ' ', trim($result));
		$valid_result = preg_replace('/\s+/', ' ', trim($valid_result));
		$this->assert_equal($result, $valid_result);

		$x = new Database_Query_Select($db);
		$x->from($table_name)->setWhatString("ID")->where("ID|!=|AND", [
			1,
			2,
			3,
			4,
		]);
		$result = strval($x);

		$result = strval($x);
		$valid_result = "SELECT ID FROM `Database_Query_Select` WHERE (`ID` != 1 AND `ID` != 2 AND `ID` != 3 AND `ID` != 4)";

		$result = preg_replace('/\s+/', ' ', trim($result));
		$valid_result = preg_replace('/\s+/', ' ', trim($valid_result));
		$this->assert($result === "$valid_result", "\"$result\" === \"$valid_result\"");

		$x = new Database_Query_Select($db);
		$x->from($table_name)->setWhatString("ID");
		$x->where("*SUM(Total)|!=|AND", [
			1,
			2,
			3,
			4,
		]);
		$result = strval($x);

		$result = strval($x);
		$valid_result = "SELECT ID FROM `Database_Query_Select` WHERE (SUM(Total)!=1 AND SUM(Total)!=2 AND SUM(Total)!=3 AND SUM(Total)!=4)";

		$result = preg_replace('/\s+/', ' ', trim($result));
		$valid_result = preg_replace('/\s+/', ' ', trim($valid_result));

		$this->assert($result === "$valid_result", "\"$result\" === \"$valid_result\"");
	}
}
