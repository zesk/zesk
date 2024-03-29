<?php
declare(strict_types=1);

namespace zesk\ORM;

use zesk\Application;
use zesk\Database\Base;
use zesk\Database\DatabaseUnitTest;
use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\SQLException;
use zesk\Database\Exception\TableNotFound;
use zesk\Exception\Semantics;
use zesk\Kernel;
use zesk\ORM\Exception\ORMEmpty;
use zesk\ORM\Exception\ORMNotFound;
use zesk\Types;

abstract class ORMUnitTest extends DatabaseUnitTest {
	/**
	 * @param ORMBase $object
	 * @return void
	 * @throws Duplicate
	 * @throws SQLException
	 * @throws TableNotFound
	 */
	final protected function prepareORMTable(ORMBase $object): void {
		$this->dropAndCreateTable($object->table(), $object->schema());
		$object->schemaChanged();
	}

	/**
	 * Must not call in dataProvider
	 *
	 * @return Application
	 * @throws Semantics
	 */
	public static function app(): Application {
		return Kernel::singleton()->application();
	}

	public function requireORMTables(string|array $classes): void {
		foreach (Types::toList($classes) as $class) {
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
	public function assertORMClass(string $class, mixed $mixed = null, array $options = [], string $test_field =
	'id'): void {
		$this->assertORMObject($this->application->ormFactory($class, $mixed, $options), $test_field);
	}

	/**
	 * @not_test
	 */
	final public function assertORMObject(ORMBase $object, string $test_field = 'id'): void {
		$table = $object->table();
		$this->assertIsString($table);

		$db = $object->database();
		$options = [
			'follow' => true,
		];
		$results = $this->application->ormModule()->schemaSynchronize($db, [
			$object::class,
		], $options);
		if (count($results) > 0) {
			$db->queries($results);
		}

		$this->assertTrue($object->database()->tableExists($table), "$table does not exist");

		$schema = $object->schema();
		$this->assertInstanceOf(Schema::class, $schema);

		$findKeys = $object->findKeys();

		if (count($findKeys) > 1) {
			$this->assertNull($object->findKey());
		} else {
			$this->assertIsString($object->findKey());
		}

		$this->assertIsArray($object->findKeys());

		$this->assertIsArray($object->duplicateKeys());

		$this->assertInstanceOf(Base::class, $object->database());

		$this->assertIsString($object->className());

		$this->assertIsString($object->idColumn());

		$this->assertTrue($object->utcTimestamps());

		$this->assertEquals($object, $object->selectDatabase());

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

		$object->members([]);

		$object->memberIsEmpty($f);

		$overwrite = true;
		$object->setMember($f, $v, $overwrite);

		$f = $test_field;
		$object->hasMember($f);

		try {
			$this->assertInstanceOf($object::class, $object->exists());
			$this->fail('Should throw ' . ORMNotFound::class);
		} catch (ORMNotFound $e) {
			$this->assertInstanceOf(ORMNotFound::class, $e);
		}

		try {
			$this->assertInstanceOf($object::class, $object->find());
			$this->fail('Should throw ' . ORMNotFound::class);
		} catch (ORMNotFound $e) {
			$this->assertInstanceOf(ORMNotFound::class, $e);
		}


		$object->isDuplicate();

		try {
			$this->assertInstanceOf($object::class, $object->fetchByKey(2, $test_field));
			$this->fail('Should throw ' . ORMNotFound::class);
		} catch (ORMNotFound $e) {
			$this->assertInstanceOf(ORMNotFound::class, $e);
		}

		try {
			$object->fetch();
			$this->fail('Should throw ' . ORMEmpty::class);
		} catch (ORMEmpty) {
		}

		$columns = $object->columns();
		$this->assertNotCount(0, $columns);

		foreach ($columns as $member) {
			$type = $object->class_orm()->column_types[$member] ?? '';
			if ($type === Class_Base::TYPE_STRING) {
				$object->__set($member, 'stuff' . mt_rand());
			}
		}
		$object->__toString();

		$template_name = 'view';
		$object->theme($template_name);
	}
}
