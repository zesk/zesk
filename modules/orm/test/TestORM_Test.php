<?php
declare(strict_types=1);

namespace zesk;

class TestORM_Test extends Test_ORM {
	protected array $load_modules = [
		'MySQL',
		'ORM',
	];

	public function initialize(): void {
		require_once __DIR__ . '/TestORM_Test_Objects.php';
		parent::initialize();
	}

	public function object_tests(ORM $x): void {
		$x->schema();

		$x->table();

		$x->findKey();

		$x->findKeys();

		$x->duplicateKeys();

		$x->database();

		$x->idColumn();

		$x->utc_timestamps();

		$x->selectDatabase();

		$x->refresh();

		$mixed = null;
		$x->initialize($mixed);

		$x->is_new();

		$x->clear();

		$x->display_name();

		$x->id();

		$f = 'Foo';
		$x->__get($f);

		$f = 'Foo';
		$v = null;
		$x->__set($f, $v);

		$f = 'Foo';
		$def = null;
		$x->member($f, $def);

		$f = 'Foo';
		$x->members_changed($f);

		$x->changed();

		$f = 'Foo';
		$def = null;
		$x->membere($f, $def);

		$f = 'Foo';
		$def = null;
		$x->member_timestamp($f, $def);

		$this->assertEquals([], $x->members([]));

		$f = 'Foo';
		$x->memberIsEmpty($f);

		$f = 'Foo';
		$v = null;
		$overwrite = true;
		$x->set_member($f, $v, $overwrite);

		$mixed = 'Hello';
		$x->memberKeysRemove($mixed);

		$f = 'Foo';
		$x->hasMember($f);

		//$x->insert();

		//$x->update();

		try {
			$where = '';
			$x->exists($where);
			$this->assertFalse(true, 'Should throw');
		} catch (Exception_ORM_NotFound) {
			$this->assertTrue(true, 'Not found');
		}

		try {
			$where = [];
			$x->find($where);
			$this->assertFalse(true, 'Should throw');
		} catch (Exception_ORM_NotFound) {
			$this->assertTrue(true, 'Not found');
		}

		$x->isDuplicate();

		try {
			$value = '';
			$column = '';
			$x->fetchByKey($value, $column);
			$this->assertFalse(true, 'Should throw');
		} catch (Exception_ORM_NotFound) {
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

		$this->test_table($sTable);

		$mixed = null;
		$options = [];
		$x = new TestORM($this->application, $mixed, $options);

		$this->object_tests($x);

		echo basename(__FILE__) . ": success\n";
	}

	/**
	 * @no_test
	 * @expectedException Exception_Semantics
	 *
	 * @param ORM $object
	 */
	public function test_object_ordering_fail(ORM $object): void {
		$id0 = null;
		$id1 = null;
		$order_column = 'OrderIndex';
		$object->reorder($id0, $id1, $order_column);
	}

	public function data_mixedToClass() {
		$test_orm = new TestORM($this->application);
		return [
			['ClassName', 'ClassName'],
			[$test_orm, 'zesk\\TestORM'],
			[$test_orm::class, 'zesk\\TestORM'],
			[$test_orm->class_orm(), 'zesk\\TestORM'],
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
			$this->assertEquals($expected_class, ORM::mixedToClass($mixed));
		} catch (Exception_Parameter) {
			$this->assertEquals($expected_class, '', 'mixedToClass should have failed with a parameter exception');
		}
	}
}
