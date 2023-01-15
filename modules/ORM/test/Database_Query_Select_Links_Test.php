<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\ORM;

class Database_Query_Select_Links_Test extends ORMUnitTest {
	protected array $load_modules = [
		'MySQL',
		'ORM',
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Test::initialize()
	 */
	public function initialize(): void {
		require_once __DIR__ . '/Database_Query_Select_Links_Test_Objects.php';
		$this->schemaSynchronize([TestPerson::class]);
	}

	public function sep(): void {
		echo str_repeat('-', 60) . "\n";
	}

	public function sql_normalize($sql) {
		$sql = strtr($sql, [
			' = ' => '=',
		]);
		return $sql;
	}

	public function sqlTestAssert($expected, $actual, $test = null): void {
		$expected = trim($expected);
		$actual = trim($actual);
		$expected = $this->sql_normalize($expected);
		$actual = $this->sql_normalize($actual);
		$this->assertEquals($expected, $actual);
	}

	public function test_main(): void {
		$db = $this->application->database_registry();
		$db->tablePrefix('');

		/*==== Test ===============================================================*/

		$query = $this->application->ormRegistry(__NAMESPACE__ . '\\' . 'Test_SiteMonitor')->querySelect()->link('Test_Account', ['path' => 'Site.Account']);
		$query->addWhere('Account.Cancelled', null);

		$expected = 'SELECT `X`.* FROM `Test_SiteMonitor` AS `X`
INNER JOIN `Test_Site` AS `Site` ON `Site`.`ID`=`X`.`Site`
INNER JOIN `Test_Account` AS `Account` ON `Account`.`ID`=`Site`.`Account`
WHERE `Account`.`Cancelled` IS NULL';

		$result = strval($query);
		$this->sqlTestAssert($expected, $result, 'link through');

		/*==== Test ===============================================================*/

		$query = $this->application->ormRegistry(__NAMESPACE__ . '\\' . 'Test_SiteMonitor')->querySelect();
		$query->link(__NAMESPACE__ . '\\' . 'Test_Site');
		$query->link(__NAMESPACE__ . '\\' . 'Test_Account', 'Site.Account');
		$query->addWhere('Account.Cancelled', null);

		$result = strval($query);
		$this->sqlTestAssert($expected, $result, 'explicit link through');

		/*==== Test ===============================================================*/

		$query = $this->application->ormRegistry(__NAMESPACE__ . '\\' . 'Test_SiteMonitor')->querySelect();
		$query->link(__NAMESPACE__ . '\\' . 'Test_Site');
		$query->link(__NAMESPACE__ . '\\' . 'Test_Site');
		$query->link(__NAMESPACE__ . '\\' . 'Test_Site');
		$query->link(__NAMESPACE__ . '\\' . 'Test_Account', 'Site.Account');
		$query->link(__NAMESPACE__ . '\\' . 'Test_Account', 'Site.Account');
		$query->link(__NAMESPACE__ . '\\' . 'Test_Account', 'Site.Account');
		$query->addWhere('Account.Cancelled', null);

		$result = strval($query);
		$this->sqlTestAssert($expected, $result, 'explicit link through, repeated a few times');

		/*==== Test ===============================================================*/

		$query = $this->application->ormRegistry(__NAMESPACE__ . '\\' . 'Test_SiteMonitor')->querySelect();
		$query->link('Test_Account', [
			'path' => 'Site.Account',
			'alias' => 'dude',
		]);
		$query->addWhere('dude.Cancelled', null);

		$expected = 'SELECT `X`.* FROM `Test_SiteMonitor` AS `X`
INNER JOIN `Test_Site` AS `Site` ON `Site`.`ID`=`X`.`Site`
INNER JOIN `Test_Account` AS `dude` ON `dude`.`ID`=`Site`.`Account`
WHERE `dude`.`Cancelled` IS NULL';
		$result = strval($query);
		$this->sqlTestAssert($expected, $result, 'computed link through, alternate alias');

		/*==== Test ===============================================================*/

		$query = $this->application->ormRegistry(__NAMESPACE__ . '\\' . 'Test_SiteMonitor')->querySelect();
		$query->link(__NAMESPACE__ . '\\' . 'Test_Site');
		$query->link(__NAMESPACE__ . '\\' . 'Test_Account', [
			'path' => 'Site.Account',
			'alias' => 'dude',
		]);
		$query->addWhere('dude.Cancelled', null);

		$expected = 'SELECT `X`.* FROM `Test_SiteMonitor` AS `X`
INNER JOIN `Test_Site` AS `Site` ON `Site`.`ID`=`X`.`Site`
INNER JOIN `Test_Account` AS `dude` ON `dude`.`ID`=`Site`.`Account`
WHERE `dude`.`Cancelled` IS NULL';
		$result = strval($query);
		$this->sqlTestAssert($expected, $result, 'explicit link through, alternate alias');

		/*==== Test ===============================================================*/

		$query = $this->application->ormRegistry(__NAMESPACE__ . '\\' . 'Test_SiteMonitor')->querySelect();
		$query->link(__NAMESPACE__ . '\\' . 'Test_Site', [
			'alias' => 'S',
		]);
		$query->link(__NAMESPACE__ . '\\' . 'Test_Account', [
			'path' => 'S.Account',
			'alias' => 'A',
		]);
		$query->addWhere('A.Cancelled', null);

		$expected = 'SELECT `X`.* FROM `Test_SiteMonitor` AS `X`
INNER JOIN `Test_Site` AS `S` ON `S`.`ID`=`X`.`Site`
INNER JOIN `Test_Account` AS `A` ON `A`.`ID`=`S`.`Account`
WHERE `A`.`Cancelled` IS NULL';
		$result = strval($query);
		$this->sqlTestAssert($expected, $result, 'explicit link through, two aliases');

		/*==== Test ===============================================================*/

		$person = new TestPerson($this->application);
		$person->setId(1);

		/* @var $iterator ORMIterator */
		$iterator = $person->Children;
		$this->assertInstanceOf(ORMIterator::class, $iterator);
		$result = strval($iterator->query());
		$expected = 'SELECT `Children`.* FROM `TestPerson` AS `Children`
WHERE `Children`.`Parent` = 1';
		$this->sqlTestAssert($expected, $result, 'Person->Children');

		/*==== Test ===============================================================*/

		$person = new TestPerson($this->application);
		$person->setId(1);

		$iterator = $person->Pets;

		$result = strval($iterator->query());
		$expected = 'SELECT `Pets`.* FROM `TestPet` AS `Pets`
INNER JOIN `TestPersonPet` AS `Pets_join` ON `Pets_join`.`Pet`=`Pets`.`PetID`
WHERE `Pets_join`.`Person` = 1';
		$this->sqlTestAssert($expected, $result, 'Person->Pets');

		/*==== Test ===============================================================*/

		$query = $this->application->ormRegistry(TestPerson::class)->querySelect();
		$query->link(TestPet::class);
		$query->addWhere('Pets.Type', 'cat');

		$expected = '
SELECT `X`.* FROM `TestPerson` AS `X`
INNER JOIN `TestPersonPet` AS `Pets_Link_join` ON `Pets_Link_join`.`Person`=`X`.`PersonID`
INNER JOIN `TestPet` AS `Pets` ON `Pets_Link_join`.`Pet`=`Pets`.`PetID`
WHERE `Pets`.`Type` = \'cat\'
';
		$result = strval($query);
		$this->sqlTestAssert($expected, $result, 'Person->TestPet');

		/*==== Test ===============================================================*/

		$query = $this->application->ormRegistry(TestPerson::class)->querySelect();
		$query->link(TestPet::class);
		$query->link(TestPerson::class);
		$query->addWhere('Pets.Type', 'cat');

		$expected = '
SELECT `X`.* FROM `TestPerson` AS `X`
INNER JOIN `TestPersonPet` AS `Pets_Link_join` ON `Pets_Link_join`.`Person`=`X`.`PersonID`
INNER JOIN `TestPet` AS `Pets` ON `Pets_Link_join`.`Pet`=`Pets`.`PetID`
INNER JOIN `TestPerson` AS `Children` ON `Children`.`Parent`=`X`.`PersonID`
WHERE `Pets`.`Type` = \'cat\'
';
		$result = strval($query);
		$this->sqlTestAssert($expected, $result);
	}
}
