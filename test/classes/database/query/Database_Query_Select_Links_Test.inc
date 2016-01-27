<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/database/query/Database_Query_Select_Links_Test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
class Test_SiteMonitor extends Object {

	protected $id_column = "ID";

	protected $has_one = array(
		'Site' => 'Test_Site'
	);

	protected $columns = array(
		'ID',
		"Name",
		"Site"
	);
}
class Test_Site extends Object {

	protected $id_column = "ID";

	protected $has_one = array(
		'Account' => 'Test_Account'
	);

	protected $columns = array(
		'ID',
		"Name",
		"Account"
	);
}
class Test_Account extends Object {

	protected $id_column = "ID";

	protected $has_one = array(
		'Primary_Test_Site' => "Test_Site",
		'Recent_Test_Site' => "Test_Site"
	);

	protected $columns = array(
		'ID',
		"Name",
		"Primary_Site",
		"Recent_Site",
		"Cancelled"
	);
}
class TestPerson extends Object {

	protected $id_column = "PersonID";

	protected $has_many = array(
		'Favorite_Pets' => array(
			'class' => 'TestPet',
			'table' => 'TestPersonPetFavorites',
			'foreign_key' => 'Person',
			'far_key' => 'Pet'
		),
		'Pets' => array(
			'class' => 'TestPet',
			'table' => 'TestPersonPet',
			'default' => true,
			'foreign_key' => 'Person',
			'far_key' => 'Pet'
		),
		'Children' => array(
			'class' => 'TestPerson',
			'foreign_key' => 'Parent'
		)
	);

	protected $column_types = array(
		'PersonID' => Class_Object::type_id,
		"Name" => Class_Object::type_string,
		"Parent" => Class_Object::type_object
	);
}
class TestPet extends Object {

	protected $id_column = "PetID";

	protected $columns = array(
		'PetID',
		'Name',
		'Type'
	);
}
class Database_Query_Select_Links_Test extends Test_Unit {

	function sep() {
		echo str_repeat('-', 60) . "\n";
	}

	function sql_normalize($sql) {
		$sql = strtr($sql, array(
			" = " => "="
		));
		return $sql;
	}
	function sql_test_assert($result, $test_result, $test = null) {
		$result = trim($result);
		$test_result = trim($test_result);
		$result = $this->sql_normalize($result);
		$test_result = $this->sql_normalize($test_result);
		$this->assert($result === $test_result, "\nTEST: $test\n$result\n(RESULT) !== (EXPECTED)\n$test_result\n");
	}

	function test_main() {
		zesk::set('table_prefix', '');

		/*==== Test ===============================================================*/

		$query = Object::class_query('Test_SiteMonitor')->link('Test_Account', 'Site.Account');
		$query->where('Account.Cancelled', null);

		$test_result = 'SELECT `X`.* FROM `Test_SiteMonitor` AS `X`
INNER JOIN `Test_Site` AS `Site` ON `Site`.`ID`=`X`.`Site`
INNER JOIN `Test_Account` AS `Account` ON `Account`.`ID`=`Site`.`Account`
WHERE `Account`.`Cancelled` IS NULL';

		$result = strval($query);
		$this->sql_test_assert($result, $test_result, "link through");

		/*==== Test ===============================================================*/

		$query = Object::class_query('Test_SiteMonitor');
		$query->link('Test_Site');
		$query->link('Test_Account', 'Site.Account');
		$query->where('Account.Cancelled', null);

		$result = strval($query);
		$this->sql_test_assert($result, $test_result, "explicit link through");

		/*==== Test ===============================================================*/

		$query = Object::class_query('Test_SiteMonitor');
		$query->link('Test_Site');
		$query->link('Test_Site');
		$query->link('Test_Site');
		$query->link('Test_Account', 'Site.Account');
		$query->link('Test_Account', 'Site.Account');
		$query->link('Test_Account', 'Site.Account');
		$query->where('Account.Cancelled', null);

		$result = strval($query);
		$this->sql_test_assert($result, $test_result, "explicit link through, repeated a few times");

		/*==== Test ===============================================================*/

		$query = Object::class_query('Test_SiteMonitor');
		$query->link('Test_Account', array(
			'path' => 'Site.Account',
			'alias' => 'dude'
		));
		$query->where('dude.Cancelled', null);

		$test_result = 'SELECT `X`.* FROM `Test_SiteMonitor` AS `X`
INNER JOIN `Test_Site` AS `Site` ON `Site`.`ID`=`X`.`Site`
INNER JOIN `Test_Account` AS `dude` ON `dude`.`ID`=`Site`.`Account`
WHERE `dude`.`Cancelled` IS NULL';
		$result = strval($query);
		$this->sql_test_assert($result, $test_result, 'computed link through, alternate alias');

		/*==== Test ===============================================================*/

		$query = Object::class_query('Test_SiteMonitor');
		$query->link('Test_Site');
		$query->link('Test_Account', array(
			'path' => 'Site.Account',
			'alias' => 'dude'
		));
		$query->where('dude.Cancelled', null);

		$test_result = 'SELECT `X`.* FROM `Test_SiteMonitor` AS `X`
INNER JOIN `Test_Site` AS `Site` ON `Site`.`ID`=`X`.`Site`
INNER JOIN `Test_Account` AS `dude` ON `dude`.`ID`=`Site`.`Account`
WHERE `dude`.`Cancelled` IS NULL';
		$result = strval($query);
		$this->sql_test_assert($result, $test_result, 'explicit link through, alternate alias');

		/*==== Test ===============================================================*/

		$query = Object::class_query('Test_SiteMonitor');
		$query->link('Test_Site', array(
			'alias' => 'S'
		));
		$query->link('Test_Account', array(
			'path' => 'S.Account',
			'alias' => 'A'
		));
		$query->where('A.Cancelled', null);

		$test_result = 'SELECT `X`.* FROM `Test_SiteMonitor` AS `X`
INNER JOIN `Test_Site` AS `S` ON `S`.`ID`=`X`.`Site`
INNER JOIN `Test_Account` AS `A` ON `A`.`ID`=`S`.`Account`
WHERE `A`.`Cancelled` IS NULL';
		$result = strval($query);
		$this->sql_test_assert($result, $test_result, 'explicit link through, two aliases');

		/*==== Test ===============================================================*/

		$person = new TestPerson(array(
			"PersonID" => 1
		));

		/* @var $iterator Object_Iterator */
		$iterator = $person->Children;

		$result = strval($iterator->query());
		$test_result = 'SELECT `Children`.* FROM `TestPerson` AS `Children`
WHERE `Children`.`Parent` = 1';
		$this->sql_test_assert($result, $test_result, "Person->Children");

		/*==== Test ===============================================================*/

		$person = new TestPerson(array(
			"PersonID" => 1
		));

		/* @var $iterator Object_Iterator */
		$iterator = $person->Pets;

		$result = strval($iterator->query());
		$test_result = 'SELECT `Pets`.* FROM `TestPet` AS `Pets`
INNER JOIN `TestPersonPet` AS `Pets_join` ON `Pets_join`.`Pet`=`Pets`.`PetID`
WHERE `Pets_join`.`Person` = 1';
		$this->sql_test_assert($result, $test_result, "Person->Pets");

		/*==== Test ===============================================================*/

		$query = Object::class_query('TestPerson');
		$query->link('TestPet');
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

		$query = Object::class_query('TestPerson');
		$query->link('TestPet');
		$query->link('TestPerson');
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
