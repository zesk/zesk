<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\ORM;

/**
 *
 * @author kent
 *
 */
class Database_Query_Insert_Test extends ORMUnitTest {
	protected array $load_modules = [
		'MySQL',
		'ORM',
	];

	public function test_Database_Query_Insert(): void {
		/**
		 * $URL:
		 * https://code.marketacumen.com/zesk/trunk/classes/database/query/test/database_query_insert_test.inc
		 * $
		 *
		 * @package zesk
		 * @subpackage test33
		 * @author kent
		 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
		 */
		$db = $this->application->databaseRegistry();
		$testx = new Database_Query_Insert($db);

		$table = 'TABLENAME';
		$testx->setInto($table);
		$testx->setValidColumns(['thing', 'ID']);

		$name = 'thing';
		$value = null;
		$testx->value($name, $value);

		$values = [
			'ID' => '23',
		];
		$testx->setValues($values);

		$low_priority = true;
		$testx->setLowPriority($low_priority);

		$testx->setReplace(true);

		$this->assertEquals("REPLACE  LOW_PRIORITY INTO `TABLENAME` (\n	`thing`,\n	`ID`\n) VALUES (\n	NULL,\n	'23'\n)", $testx->__toString());

		$columns = [
			'ID',
		];
		$testx->setValidColumns($columns);

		$testx->database();

		$class = Server::class;
		$testx->setORMClass($class);
	}
}
