<?php
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