<?php

namespace zesk\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use zesk\Application;
use zesk\Exception\Semantics;
use zesk\Kernel;
use zesk\Types;
use zesk\UnitTest;

class ModelUnitTest extends UnitTest {
	/**
	 * @var EntityManager
	 */
	protected EntityManager $em;

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

	public function initialize(): void {
		parent::initialize();
		$this->em = $this->application->entityManager();
	}
	public function schemaSynchronize(string|array $entities): void {
		$this->application->doctrineModule()->schemaSynchronize(Types::toList($entities));
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
	 */
	final public function assertORMObject(Model $object): void {
		$e = $this->entityManager()->getRepository(get_class($object));
		$this->assertInstanceOf(ObjectRepository::class, $e);

		$this->doctrineModule()->schemaSynchronize($object::class);

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
