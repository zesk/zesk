<?php
declare(strict_types=1);

namespace zesk;

class ORMUnitTest extends DatabaseUnitTest {
	public static function setUpBeforeClass(): void {
		// pass
	}

	/**
	 * @param string|array $classes
	 * @return void
	 */
	public function require_tables(string|array $classes): void {
		zesk()->deprecated(__METHOD__);
		self::requireORMTables($classes);
	}

	public function requireORMTables(string|array $classes): void {
		foreach (toList($classes) as $class) {
			$object = $this->application->ormRegistry($class);
			$table = $object->table();
			if (!$object->database()->tableExists($table)) {
				$schema = $this->application->ormFactory($class)->schema();
				$create_sql = strval($schema);
				$this->dropAndCreateTable($table, $create_sql);
			}
		}
	}

	public function truncateClassTables(string $class, bool $related = false): void {
		$classes = [$class => $class];
		if ($related) {
			$class_orm = $this->application->ormFactory($class)->class_orm();
			foreach ($class_orm->has_one as $member => $member_class) {
				$classes[$member_class] = $member_class;
			}
			foreach ($class_orm->has_many as $member => $fields) {
				$member_class = $fields['class'];
				$classes[$member_class] = $member_class;
			}
		}
		foreach ($classes as $class) {
			$orm = $this->application->ormFactory($class);
			$orm->queryDelete()->setTruncate(true)->execute();
		}
	}

	/**
	 *
	 * @param string $class
	 * @param array $options
	 */
	public function assertORMClass(string $class, array $options = [], string $test_field = 'id') {
		return $this->assertORMObject($this->application->ormFactory($class, $options), $test_field);
	}

	/**
	 * @not_test
	 */
	final public function assertORMObject(ORM $object, string $test_field = 'id'): void {
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

		$names = $object->memberNames();
		$this->assertIsArray($names);

		$members = $object->members();
		$this->assertIsArray($members);

		$object->refresh();

		// 		$mixed = "ID";
		// 		$object->initialize($mixed);

		$object->isNew();

		$object->clear();

		$object->displayName();

		$object->id();

		$x = $test_field;
		$object->__get($x);

		$v = null;
		$f = $test_field;
		$def = null;

		$object->__set($x, $v);

		$object->member($f);

		$object->changed($f);

		$object->changed();

		$object->membere($f, $def);

		$object->members([]);

		$object->memberIsEmpty($f);

		$overwrite = true;
		$object->setMember($f, $v, $overwrite);

		$f = $test_field;
		$object->hasMember($f);

		try {
			$this->assertInstanceOf($object::class, $object->exists());
		} catch (Exception_ORM_NotFound $e) {
			$this->assertInstanceOf(Exception_ORM_NotFound::class, $e);
		}

		try {
			$this->assertInstanceOf($object::class, $object->find());
		} catch (Exception_ORM_NotFound $e) {
			$this->assertInstanceOf(Exception_ORM_NotFound::class, $e);
		}



		$object->isDuplicate();

		try {
			$this->assertInstanceOf($object::class, $object->fetchByKey(2, $test_field));
		} catch (Exception_ORM_NotFound $e) {
			$this->assertInstanceOf(Exception_ORM_NotFound::class, $e);
		}

		try {
			$object->fetch();
			$this->fail('Should throw Exception_ORM_Empty');
		} catch (Exception_ORM_Empty $e) {
		}

		$columns = $object->columns();
		$this->assertNotEquals(0, count($columns));

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
