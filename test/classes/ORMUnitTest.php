<?php
declare(strict_types=1);

namespace zesk;

class ORMUnitTest extends UnitTest {
	public function _test_object(): ORM {
		$this->assert($this->class !== null);
		return $this->application->objects->factory($this->class);
	}

	public function classes_to_test() {
		return [
			[
				'User',
				[],
			],
		];
	}

	public function require_tables(array $classes): void {
		foreach ($classes as $class) {
			$object = $this->application->ormRegistry($class);
			$table = $object->table();
			if (!$object->database()->tableExists($table)) {
				$schema = $this->application->ormFactory($class)->schema();
				$create_sql = strval($schema);
				$this->test_table_sql($table, $create_sql);
			}
		}
	}

	/**
	 *
	 * @param string $class
	 * @param array $options
	 * @dataProvider classes_to_test
	 */
	public function run_test_class($class, array $options = []) {
		return $this->run_test_an_object($this->application->ormFactory($class, $options));
	}

	/**
	 * @not_test
	 */
	final public function run_test_an_object(ORM $object, $test_field = 'ID'): void {
		$table = $object->table();
		$this->assertIsString($table);

		$db = $object->database();
		$options = [
			'follow' => true,
		];
		$results = $this->application->orm_module()->schema_synchronize($db, [
			$object::class,
		], $options);
		if (count($results) > 0) {
			$db->queries($results);
		}

		$this->assertTrue($object->database()->tableExists($table), "$table does not exist");

		$schema = $object->schema();
		$this->assertInstanceOf(ORM_Schema::class, $schema);

		$object->findKey();

		$object->findKeys();

		$object->duplicateKeys();

		$object->database();

		$object->className();

		$object->idColumn();

		$object->utcTimestamps();

		$object->selectDatabase();

		$this->log($object::class . ' members: ' . PHP::dump($object->members()));

		$object->refresh();

		// 		$mixed = "ID";
		// 		$object->initialize($mixed);

		$object->isNew();

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

		$this->log($object::class . ' members: ' . PHP::dump($object->members()));

		$f = $test_field;
		$object->changed($f);

		$object->changed();

		$f = $test_field;
		$def = null;
		$object->membere($f, $def);

		$object->members([]);

		$f = $test_field;
		$object->memberIsEmpty($f);

		$f = $test_field;
		$v = null;
		$overwrite = true;
		$object->setMember($f, $v, $overwrite);

		$mixed = $test_field;
		$object->memberKeysRemove($mixed);

		$f = $test_field;
		$object->hasMember($f);

		$where = false;
		$object->exists($where);

		$where = false;
		$object->find($where);

		$object->isDuplicate();

		$value = false;
		$column = false;
		$object->fetchByKey($value, $column);

		try {
			$object->fetch();
			$this->fail('Should throw Exception_ORM_Empty');
		} catch (Exception_ORM_Empty $e) {
		}

		$columns = $object->columns();
		$this->assert_not_equal(count($columns), 0);


		foreach ($columns as $member) {
			$type = $object->class_orm()->column_types[$member] ?? '';
			if ($type === Class_ORM::type_string) {
				$object->__set($member, 'stuff' . mt_rand());
			}
		}
		$object->__toString();

		$template_name = 'view';
		$object->theme($template_name);
	}
}
