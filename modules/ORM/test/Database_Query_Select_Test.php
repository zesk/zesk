<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

use zesk\ORM\Database_Query_Select;
use zesk\ORM\Server;
use zesk\DatabaseUnitTest;

class Database_Query_Select_Test extends DatabaseUnitTest {
	protected array $load_modules = [
		'MySQL',
		'ORM',
	];

	public function initialize(): void {
		// $this->schema_synchronize(Person::class);
	}

	public function test_main(): void {
		$table_name = 'Database_Query_Select';

		$this->prepareTestTable($table_name);

		$db = $this->application->database_registry();
		$select = new Database_Query_Select($db);

		$select->setWhatString('*');
		$select->addWhat('X', 'COUNT(ID)');
		$select->addWhat('Y', 'SUM(Total)');

		$alias = '';
		$select->from($table_name, $alias);

		$sql = 'INNER JOIN Person P ON P.ID=U.Person';
		$join_id = 'person';
		$select->addJoin($sql, $join_id);


		$select->addWhere('Name', 2);

		$select->setOrderBy(toList('A;B;C'));

		$select->setGroupBy(['X']);

		$select->setOffsetLimit(0, 1000);

		$select->__toString();

		$select->iterator();

		$select->ormIterator('zesk\\User');


		// Hits database
		//
		//		$testx->orm("User");
		//
		//		$field = "id";
		//		$default = null;
		//		$testx->one($field, $default);

		//		$field = "field2";
		//		$default = 0;
		//		$testx->integer($field, $default);

		//		$field = "field1";
		//		$default = 0;
		//		$testx->integer($field, $default);

		//		$testx->toArray();

		$select->database();

		$class = Server::class;
		$select->setORMClass($class);

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

		$this->assertSQLEquals($result, $valid_result);

		$x = new Database_Query_Select($db);
		$x->from($table_name)->setWhatString('ID')->addWhere('ID|!=|AND', [
			1,
			2,
			3,
			4,
		]);

		$result = strval($x);
		$valid_result = 'SELECT ID FROM `Database_Query_Select` WHERE (`ID` != 1 AND `ID` != 2 AND `ID` != 3 AND `ID` != 4)';

		$this->assertSQLEquals($result, $valid_result);

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

		$this->assertSQLEquals($valid_result, $result);
	}
}
