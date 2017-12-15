<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/database/query/Database_Query_Select_Links_Test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Class_Test_SiteMonitor extends Class_ORM {
	public $id_column = "ID";
	public $has_one = array(
		'Site' => __NAMESPACE__ . "\\" . 'Test_Site'
	);
	public $column_types = array(
		'ID' => self::type_id,
		"Name" => self::type_string,
		"Site" => self::type_object
	);
}
class Test_SiteMonitor extends ORM {}
class Class_Test_Site extends Class_ORM {
	public $id_column = "ID";
	public $has_one = array(
		'Account' => __NAMESPACE__ . '\\' . 'Test_Account'
	);
	public $column_types = array(
		'ID' => self::type_id,
		"Name" => self::type_string,
		"Account" => self::type_object
	);
}
class Test_Site extends ORM {}
class Class_Test_Account extends Class_ORM {
	public $id_column = "ID";
	public $has_one = array(
		'Primary_Test_Site' => __NAMESPACE__ . '\\' . "Test_Site",
		'Recent_Test_Site' => __NAMESPACE__ . '\\' . "Test_Site"
	);
	public $column_types = array(
		'ID' => self::type_id,
		"Name" => self::type_string,
		"Primary_Site" => self::type_object,
		"Recent_Site" => self::type_object,
		"Cancelled" => self::type_timestamp
	);
}
class Test_Account extends ORM {}
class Class_TestPerson extends Class_ORM {
	public $id_column = "PersonID";
	public $has_many = array(
		'Favorite_Pets' => array(
			'class' => __NAMESPACE__ . '\\' . 'TestPet',
			'table' => __NAMESPACE__ . '\\' . 'TestPersonPetFavorites',
			'foreign_key' => 'Person',
			'far_key' => 'Pet'
		),
		'Pets' => array(
			'class' => __NAMESPACE__ . '\\' . 'TestPet',
			'link_class' => __NAMESPACE__ . '\\' . 'TestPersonPet',
			'default' => true,
			'foreign_key' => 'Person',
			'far_key' => 'Pet'
		),
		'Children' => array(
			'class' => __NAMESPACE__ . '\\' . 'TestPerson',
			'foreign_key' => 'Parent'
		)
	);
	public $column_types = array(
		'PersonID' => Class_ORM::type_id,
		"Name" => Class_ORM::type_string,
		"Parent" => Class_ORM::type_object
	);
}
class TestPersonPet extends ORM {}
class Class_TestPersonPet extends Class_ORM {
	public $column_types = array(
		'Person' => self::type_object,
		'Pet' => self::type_object
	);
}
class TestPerson extends ORM {}
class Class_TestPet extends Class_ORM {
	public $id_column = "PetID";
	public $column_types = array(
		'PetID' => self::type_id,
		'Name' => self::type_string,
		'Type' => self::type_object
	);
}
class TestPet extends ORM {}
class Database_Query_Select_Links_Test extends Test_Unit {
	protected $load_modules = array(
		"MySQL"
	);
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
		$db = $this->application->database_factory();
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
		
		$query = $this->application->orm_registry(__NAMESPACE__ . '\\' . 'Test_SiteMonitor')->query_select();
		$query->link(__NAMESPACE__ . '\\' . 'Test_Site');
		$query->link(__NAMESPACE__ . '\\' . 'Test_Account', array(
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
		
		$query = $this->application->orm_registry(__NAMESPACE__ . '\\' . 'Test_SiteMonitor')->query_select();
		$query->link(__NAMESPACE__ . '\\' . 'Test_Site', array(
			'alias' => 'S'
		));
		$query->link(__NAMESPACE__ . '\\' . 'Test_Account', array(
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
		
		$person = new TestPerson($this->application, array(
			"PersonID" => 1
		));
		
		/* @var $iterator ORMIterator */
		$iterator = $person->Children;
		
		$result = strval($iterator->query());
		$test_result = 'SELECT `Children`.* FROM `TestPerson` AS `Children`
WHERE `Children`.`Parent` = 1';
		$this->sql_test_assert($result, $test_result, "Person->Children");
		
		/*==== Test ===============================================================*/
		
		$person = new TestPerson($this->application, array(
			"PersonID" => 1
		));
		
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
