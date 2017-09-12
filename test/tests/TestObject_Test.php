<?php
namespace zesk;

class TestObject_Test extends Test_Object {
	protected $load_modules = array(
		"MySQL"
	);
	public static function object_tests(Object $x) {
		$x->schema();
		
		$x->table();
		
		$x->find_key();
		
		$x->find_keys();
		
		$x->duplicate_keys();
		
		$x->database();
		
		$x->id_column();
		
		$x->utc_timestamps();
		
		$x->select_database();
		
		$x->refresh();
		
		$mixed = null;
		$x->initialize($mixed);
		
		$x->is_new();
		
		$x->clear();
		
		$x->display_name();
		
		$x->id();
		
		$f = "Foo";
		$x->__get($f);
		
		$f = "Foo";
		$v = null;
		$x->__set($f, $v);
		
		$f = "Foo";
		$def = null;
		$x->member($f, $def);
		
		$f = "Foo";
		$x->members_changed($f);
		
		$x->changed();
		
		$f = "Foo";
		$def = null;
		$x->membere($f, $def);
		
		$f = "Foo";
		$def = null;
		$x->member_timestamp($f, $def);
		
		$mixed = false;
		$x->members($mixed);
		
		$f = "Foo";
		$x->member_is_empty($f);
		
		$f = "Foo";
		$v = null;
		$overwrite = true;
		$x->set_member($f, $v, $overwrite);
		
		$mixed = "Hello";
		$x->member_remove($mixed);
		
		$f = "Foo";
		$x->has_member($f);
		
		//$x->insert();
		
		//$x->update();
		
		$where = false;
		$x->exists($where);
		
		$where = false;
		$x->find($where);
		
		$x->is_duplicate();
		
		$value = false;
		$column = false;
		$x->fetch_by_key($value, $column);
		
		//	TODO 	$x->fetch();
		
		// 	TODO 	$x->Foo = 232;
		// 	TODO 	$x->store();
		
		//	TODO 	$x->register();
		
		// TODO $x->delete();
		
		$x->__toString();
		
		$template_name = 'view';
		$options = false;
		$x->theme($template_name, $options);
		
		$x->option();
		
		$remove = false;
		$x->options_exclude($remove);
		
		$selected = false;
		$x->options_include($selected);
		
		$x->option_keys();
		
		$name = null;
		$checkEmpty = false;
		$x->has_option($name, $checkEmpty);
		
		$mixed = null;
		$value = false;
		$overwrite = true;
		$x->set_option($mixed, $value, $overwrite);
		
		$name = null;
		$default = false;
		$x->option($name, $default);
		
		$name = null;
		$default = false;
		$x->option_bool($name, $default);
		
		$name = null;
		$default = false;
		$x->option_integer($name, $default);
		
		$name = null;
		$default = false;
		$x->option_double($name, $default);
		
		$name = null;
		$default = false;
		$x->option_array($name, $default);
		
		$name = null;
		$default = false;
		$delimiter = ';';
		$x->option_list($name, $default, $delimiter);
	}
	function test_object() {
		$sTable = "TestObject";
		
		$this->test_table($sTable);
		
		$mixed = null;
		$options = false;
		$x = new TestObject($this->application, $mixed, $options);
		
		$this->object_tests($x);
		
		echo basename(__FILE__) . ": success\n";
	}
	
	/**
	 * @no_test
	 * @expectedException Exception_Semantics
	 *
	 * @param Object $object
	 */
	function test_object_ordering_fail(Object $object) {
		$id0 = null;
		$id1 = null;
		$order_column = 'OrderIndex';
		$object->reorder($id0, $id1, $order_column);
	}
}
class Class_TestObject extends Class_Object {
	public $id_column = "ID";
	public $column_types = array(
		"ID" => self::type_id,
		'Name' => self::type_string,
		'Price' => self::type_double,
		'Foo' => self::type_integer
	);
}
class TestObject extends Object {
	function schema() {
		$table = $this->table;
		return array(
			"CREATE TABLE $table ( ID integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY, Name varchar(32) NOT NULL, Price decimal(12,2), Foo integer NULL )"
		);
	}
	function specification() {
		return array(
			"table" => get_class($this),
			"fields" => "Foo",
			"find_keys" => array(
				"Foo"
			)
		);
	}
}

