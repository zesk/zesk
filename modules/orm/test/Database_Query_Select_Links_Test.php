<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Database_Query_Select_Links_Test extends Test_Unit {
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
		require __DIR__ . "/Database_Query_Select_Links_Test_Objects.php";
	}

	public function sep(): void {
		echo str_repeat('-', 60) . "\n";
	}

	public function sql_normalize($sql) {
		$sql = strtr($sql, [
			" = " => "=",
		]);
		return $sql;
	}

	public function sql_test_assert($result, $test_result, $test = null): void {
		$result = trim($result);
		$test_result = trim($test_result);
		$result = $this->sql_normalize($result);
		$test_result = $this->sql_normalize($test_result);
		$this->assertEquals($result, $test_result);
	}

	public function test_main(): void {
		$db = $this->application->database_registry();
		$db->table_prefix("");

		/*==== Test ===============================================================*/

		$query = $this->application->orm_registry(__NAMESPACE__ . '\\' . 'Test_SiteMonitor')->query_select()->link('Test_Account', 'Site.Account');
		$query->where('Account.Cancelled', null);

		$test_result = 'SELECT `X`.* FROM `Test_SiteMonitor` AS `X`
INNER JOIN `Test_Site` AS `Site` ON `Site`.`ID`=`X`.`Site`
INNER JOIN `Test_Account` AS `Account` ON `Account`.`ID`=`Site`.`Account`
WHERE `Account`.`Cancelled` IS NULL';

		$result = strval($query);
		$this->sql_test_assert($result, $test_result, "link through");

		/*==== Test ===============================================================*/

		$query = $this->application->orm_registry(__NAMESPACE__ . '\\' . 'Test_SiteMonitor')->query_select();
		$query->link(__NAMESPACE__ . '\\' . 'Test_Site');
		$query->link(__NAMESPACE__ . '\\' . 'Test_Account', 'Site.Account');
		$query->where('Account.Cancelled', null);

		$result = strval($query);
		$this->sql_test_assert($result, $test_result, "explicit link through");

		/*==== Test ===============================================================*/

		$query = $this->application->orm_registry(__NAMESPACE__ . '\\' . 'Test_SiteMonitor')->query_select();
		$query->link(__NAMESPACE__ . '\\' . 'Test_Site');
		$query->link(__NAMESPACE__ . '\\' . 'Test_Site');
		$query->link(__NAMESPACE__ . '\\' . 'Test_Site');
		$query->link(__NAMESPACE__ . '\\' . 'Test_Account', 'Site.Account');
		$query->link(__NAMESPACE__ . '\\' . 'Test_Account', 'Site.Account');
		$query->link(__NAMESPACE__ . '\\' . 'Test_Account', 'Site.Account');
		$query->where('Account.Cancelled', null);

		$result = strval($query);
		$this->sql_test_assert($result, $test_result, "explicit link through, repeated a few times");

		/*==== Test ===============================================================*/

		$query = $this->application->orm_registry(__NAMESPACE__ . '\\' . 'Test_SiteMonitor')->query_select();
		$query->link('Test_Account', [
			'path' => 'Site.Account',
			'alias' => 'dude',
		]);
		$query->where('dude.Cancelled', null);

		$test_result = 'SELECT `X`.* FROM `Test_SiteMonitor` AS `X`
INNER JOIN `Test_Site` AS `Site` ON `Site`.`ID`=`X`.`Site`
INNER JOIN `Test_Account` AS `dude` ON `dude`.`ID`=`Site`.`Account`
WHERE `dude`.`Cancelled` IS NULL';
		$result = strval($query);
		$this->sql_test_assert($result, $test_result, 'computed link through, alternate alias');

		/*==== Test ===============================================================*/

		$query = $this->application->orm_registry(__NAMESPACE__ . '\\' . 'Test_SiteMonitor')->query_select();
		$query->link(__NAMESPACE__ . '\\' . 'Test_Site');
		$query->link(__NAMESPACE__ . '\\' . 'Test_Account', [
			'path' => 'Site.Account',
			'alias' => 'dude',
		]);
		$query->where('dude.Cancelled', null);

		$test_result = 'SELECT `X`.* FROM `Test_SiteMonitor` AS `X`
INNER JOIN `Test_Site` AS `Site` ON `Site`.`ID`=`X`.`Site`
INNER JOIN `Test_Account` AS `dude` ON `dude`.`ID`=`Site`.`Account`
WHERE `dude`.`Cancelled` IS NULL';
		$result = strval($query);
		$this->sql_test_assert($result, $test_result, 'explicit link through, alternate alias');

		/*==== Test ===============================================================*/

		$query = $this->application->orm_registry(__NAMESPACE__ . '\\' . 'Test_SiteMonitor')->query_select();
		$query->link(__NAMESPACE__ . '\\' . 'Test_Site', [
			'alias' => 'S',
		]);
		$query->link(__NAMESPACE__ . '\\' . 'Test_Account', [
			'path' => 'S.Account',
			'alias' => 'A',
		]);
		$query->where('A.Cancelled', null);

		$test_result = 'SELECT `X`.* FROM `Test_SiteMonitor` AS `X`
INNER JOIN `Test_Site` AS `S` ON `S`.`ID`=`X`.`Site`
INNER JOIN `Test_Account` AS `A` ON `A`.`ID`=`S`.`Account`
WHERE `A`.`Cancelled` IS NULL';
		$result = strval($query);
		$this->sql_test_assert($result, $test_result, 'explicit link through, two aliases');

		/*==== Test ===============================================================*/

		$person = new TestPerson($this->application, [
			"PersonID" => 1,
		]);

		/* @var $iterator ORMIterator */
		$iterator = $person->Children;

		$result = strval($iterator->query());
		$test_result = 'SELECT `Children`.* FROM `TestPerson` AS `Children`
WHERE `Children`.`Parent` = 1';
		$this->sql_test_assert($result, $test_result, "Person->Children");

		/*==== Test ===============================================================*/

		$person = new TestPerson($this->application, [
			"PersonID" => 1,
		]);

		/* @var $iterator ORMIterator */
		$iterator = $person->Pets;

		$result = strval($iterator->query());
		$test_result = 'SELECT `Pets`.* FROM `TestPet` AS `Pets`
INNER JOIN `TestPersonPet` AS `Pets_join` ON `Pets_join`.`Pet`=`Pets`.`PetID`
WHERE `Pets_join`.`Person` = 1';
		$this->sql_test_assert($result, $test_result, "Person->Pets");

		/*==== Test ===============================================================*/

		$query = $this->application->orm_registry(__NAMESPACE__ . '\\' . 'TestPerson')->query_select();
		$query->link(__NAMESPACE__ . '\\' . 'TestPet');
		$query->where('Pets.Type', 'cat');

		$test_result = '
SELECT `X`.* FROM `TestPerson` AS `X`
INNER JOIN `TestPersonPet` AS `Pets_Link_join` ON `Pets_Link_join`.`Person`=`X`.`PersonID`
INNER JOIN `TestPet` AS `Pets` ON `Pets_Link_join`.`Pet`=`Pets`.`PetID`
WHERE `Pets`.`Type` = \'cat\'
';
		$result = strval($query);
		$this->sql_test_assert($result, $test_result, "Person->TestPet");

		/*==== Test ===============================================================*/

		$query = $this->application->orm_registry(__NAMESPACE__ . '\\' . 'TestPerson')->query_select();
		$query->link(__NAMESPACE__ . '\\' . 'TestPet');
		$query->link(__NAMESPACE__ . '\\' . 'TestPerson');
		$query->where('Pets.Type', 'cat');

		$test_result = '
SELECT `X`.* FROM `TestPerson` AS `X`
INNER JOIN `TestPersonPet` AS `Pets_Link_join` ON `Pets_Link_join`.`Person`=`X`.`PersonID`
INNER JOIN `TestPet` AS `Pets` ON `Pets_Link_join`.`Pet`=`Pets`.`PetID`
INNER JOIN `TestPerson` AS `Children` ON `Children`.`Parent`=`X`.`PersonID`
WHERE `Pets`.`Type` = \'cat\'
';
		$result = strval($query);
		$this->sql_test_assert($result, $test_result);
	}
}
