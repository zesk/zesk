<?php
namespace zesk;

class Test_Object extends Test_Unit {
	function _test_object() {
		$this->assert($this->class !== null);
		return $this->application->objects->factory($this->class);
	}
	function classes_to_test() {
		return array(
			array(
				"User",
				array()
			)
		);
	}
	function require_tables($classes) {
		$classes = to_list($classes);
		foreach ($classes as $class) {
			$object = $this->application->object($class);
			$table = $object->table();
			if (!$object->database()->table_exists($table)) {
				$this->test_table_sql($table, $this->application->object_factory($class)->schema());
			}
		}
	}
	/**
	 *
	 * @param string $class
	 * @param array $options
	 *        	@data_provider classes_to_test
	 */
	function run_test_class($class, array $options = array()) {
		return $this->test_an_object($this->application->objects->factory($class, $options));
	}
	
	/**
	 * @not_test
	 */
	final function run_test_an_object(Object $object, $test_field = "ID") {
		$this->log(get_class($object) . " members: " . PHP::dump($object->members()));
		
		$table = $object->table();
		
		$db = $object->database();
		$results = $this->application->schema_synchronize($db, array(
			get_class($object)
		));
		if (count($results) > 0) {
			$db->query($results);
		}
		
		$this->assert($object->database()->table_exists($table), "Table not created/exists");
		
		$object->schema();
		
		$object->table();
		
		$object->find_key();
		
		$object->find_keys();
		
		$object->duplicate_keys();
		
		$object->database();
		
		$object->class_name();
		
		$object->id_column();
		
		$object->utc_Timestamps();
		
		$object->select_database();
		
		$this->log(get_class($object) . " members: " . PHP::dump($object->members()));
		
		$object->refresh();
		
		// 		$mixed = "ID";
		// 		$object->initialize($mixed);
		
		$object->is_new();
		
		$object->clear();
		
		$object->display_name();
		
		$object->id();
		
		$x = $test_field;
		$object->__get($x);
		
		$x = $test_field;
		$v = null;
		$object->__set($x, $v);
		
		$f = $test_field;
		$def = null;
		$object->member($f, $def);
		
		$this->log(get_class($object) . " members: " . PHP::dump($object->members()));
		
		$f = $test_field;
		$object->changed($f);
		
		$object->changed();
		
		$f = $test_field;
		$def = null;
		$object->membere($f, $def);
		
		$mixed = false;
		$object->members($mixed);
		
		$f = $test_field;
		$object->member_is_empty($f);
		
		$f = $test_field;
		$v = null;
		$overwrite = true;
		$object->set_member($f, $v, $overwrite);
		
		$mixed = $test_field;
		$object->member_remove($mixed);
		
		$f = $test_field;
		$object->has_member($f);
		
		$where = false;
		$object->exists($where);
		
		$where = false;
		$object->find($where);
		
		$object->is_duplicate();
		
		$value = false;
		$column = false;
		$object->fetch_by_key($value, $column);
		
		try {
			$object->fetch();
			$this->fail("Should throw Exception_Object_Empty");
		} catch (Exception_Object_Empty $e) {
		}
		
		$columns = $object->columns();
		$this->assert_not_equal(count($columns), 0);
		
		foreach ($columns as $member) {
			if (!in_array($member, $object->primary_keys())) {
				$object->__set($member, "stuff" . mt_rand());
			}
		}
		echo PHP::dump($object->members());
		// 		$object->store();
		
		// 		$object->register();
		
		//		$object->delete();
		
		$object->__toString();
		
		$template_name = 'view';
		$object->theme($template_name);
	}
}
