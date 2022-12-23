<?php
declare(strict_types=1);

namespace zesk\ORM;

use zesk\Exception_Parameter;
use zesk\Exception_Semantics;
use zesk\Timestamp;

class TestORM_Test extends ORMUnitTest {
	protected array $load_modules = [
		'MySQL',
		'ORM',
	];

	public function initialize(): void {
		require_once __DIR__ . '/TestORM_Test_Objects.php';
	}

	public function object_tests(ORMBase $x): void {
		$x->schema();

		$x->table();

		$x->findKey();

		$x->findKeys();

		$x->duplicateKeys();

		$x->database();

		$x->idColumn();

		$x->utcTimestamps();

		$x->selectDatabase();

		$x->refresh();

		$mixed = null;
		$x->initialize($mixed);

		$x->isNew();

		$x->clear();

		$x->displayName();

		$x->id();

		$f = 'Foo';
		$def = null;
		$v = null;

		$x->__get($f);

		$x->__set($f, $v);

		$x->setMember($f, 'bar');

		$x->membersChanged($f);

		$x->changed();

		$x->setMember($f, '2022-12-22 08:55:45');
		$this->assertInstanceOf(Timestamp::class, $x->member_timestamp($f, $def));

		$this->assertEquals([], $x->members([]));

		$f = 'Foo';
		$x->memberIsEmpty($f);

		$f = 'Foo';
		$v = null;
		$overwrite = true;
		$x->setMember($f, $v, $overwrite);

		$f = 'Foo';
		$x->hasMember($f);

		//$x->insert();

		//$x->update();

		try {
			$where = '';
			$x->exists($where);
			$this->assertFalse(true, 'Should throw');
		} catch (Exception_ORMNotFound) {
			$this->assertTrue(true, 'Not found');
		}

		try {
			$where = [];
			$x->find($where);
			$this->assertFalse(true, 'Should throw');
		} catch (Exception_ORMNotFound) {
			$this->assertTrue(true, 'Not found');
		}

		$x->isDuplicate();

		try {
			$value = '';
			$column = '';
			$x->fetchByKey($value, $column);
			$this->assertFalse(true, 'Should throw');
		} catch (Exception_ORMNotFound) {
			$this->assertTrue(true, 'Not found');
		}
		//	TODO 	$x->fetch();

		// 	TODO 	$x->Foo = 232;
		// 	TODO 	$x->store();

		//	TODO 	$x->register();

		// TODO $x->delete();

		$x->__toString();

		$template_name = 'view';
		$options = [];
		$x->theme($template_name, $options);
	}

	public function test_object(): void {
		$sTable = 'TestORM';

		$this->prepareTestTable($sTable);

		$mixed = null;
		$options = [];
		$x = new TestORM($this->application, $mixed, $options);

		$this->object_tests($x);
	}

	public function data_mixedToClass() {
		$this->setUp();
		$test_orm = new TestORM($this->application);
		return [
			['ClassName', 'ClassName'],
			[$test_orm, 'zesk\\ORM\\TestORM'],
			[$test_orm::class, 'zesk\\ORM\\TestORM'],
			[$test_orm->class_orm(), 'zesk\\ORM\\TestORM'],
			[0, ''],
			[23.2, ''],
			[null, ''],
			['', ''],
			[new \stdClass(), ''],
			[[], ''],
		];
	}

	/**
	 * @param mixed $mixed
	 * @param string $expected_class
	 * @return void
	 * @throws Exception_Parameter
	 * @dataProvider data_mixedToClass
	 */
	public function test_mixedToClass(mixed $mixed, string $expected_class): void {
		try {
			$this->assertEquals($expected_class, ORMBase::mixedToClass($mixed));
		} catch (Exception_Parameter) {
			$this->assertEquals($expected_class, '', 'mixedToClass should have failed with a parameter exception');
		}
	}
}
