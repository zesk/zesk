<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

class Database_Query_Select_Test extends UnitTest {
	protected array $load_modules = [
		'MySQL',
		'ORM',
	];

	public function initialize(): void {
		// $this->schema_synchronize(Person::class);
	}

	public function test_main(): void {
		$table_name = 'Database_Query_Select';

		$this->test_table($table_name);

		$db = $this->application->database_registry();
		$testx = new Database_Query_Select($db);

		$testx->setWhatString('*');
		$testx->addWhat('X', 'COUNT(ID)');
		$testx->addWhat('Y', 'SUM(Total)');

		$alias = '';
		$testx->from($table_name, $alias);

		$sql = 'INNER JOIN Person P ON P.ID=U.Person';
		$join_id = 'person';
		$testx->addJoin($sql, $join_id);

		$k = null;
		$v = null;
		$testx->addWhere('Name', 2);

		$testx->setOrderBy(toList('A;B;C'));

		$testx->setGroupBy(['X']);

		$testx->setOffsetLimit(0, 1000);

		$testx->__toString();

		$testx->iterator();

		$testx->ormIterator('zesk\\User');


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

		//		$testx->toArray();

		$testx->database();

		$class = Server::class;
		$testx->setORMClass($class);

		$db = $this->application->database_registry();
		$x = new Database_Query_Select($db);
		$x->from($table_name);
		$x->setWhatString('ID');
		$x->addWhere('ID', [
			1,
			2,
			3,
			4,
		]);

		$result = strval($x);
		$valid_result = 'SELECT ID FROM `Database_Query_Select` WHERE (`ID` = 1 OR `ID` = 2 OR `ID` = 3 OR `ID` = 4)';

		$result = preg_replace('/\s+/', ' ', trim($result));
		$valid_result = preg_replace('/\s+/', ' ', trim($valid_result));
		$this->assertEquals($result, $valid_result);

		$x = new Database_Query_Select($db);
		$x->from($table_name)->setWhatString('ID')->addWhere('ID|!=|AND', [
			1,
			2,
			3,
			4,
		]);
		$result = strval($x);

		$result = strval($x);
		$valid_result = 'SELECT ID FROM `Database_Query_Select` WHERE (`ID` != 1 AND `ID` != 2 AND `ID` != 3 AND `ID` != 4)';

		$result = preg_replace('/\s+/', ' ', trim($result));
		$valid_result = preg_replace('/\s+/', ' ', trim($valid_result));
		$this->assertEquals("$valid_result", "\"$result\" === \"$valid_result\"", $result);

		$x = new Database_Query_Select($db);
		$x->from($table_name)->setWhatString('ID');
		$x->addWhere('*SUM(Total)|!=|AND', [
			1,
			2,
			3,
			4,
		]);
		$result = strval($x);

		$result = strval($x);
		$valid_result = 'SELECT ID FROM `Database_Query_Select` WHERE (SUM(Total)!=1 AND SUM(Total)!=2 AND SUM(Total)!=3 AND SUM(Total)!=4)';

		$result = preg_replace('/\s+/', ' ', trim($result));
		$valid_result = preg_replace('/\s+/', ' ', trim($valid_result));

		$this->assertEquals("$valid_result", "\"$result\" === \"$valid_result\"", $result);
	}
}
