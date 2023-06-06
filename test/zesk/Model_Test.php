<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @copyright &copy; 2023 Market Acumen, Inc.
 * @author kent
 */

namespace zesk;

class TestModel extends Model {
	public string $thing = '';

	public int $thingTwo = 0;

	public function setId(mixed $set): self {
		if (is_int($set)) {
			$this->thingTwo = $set;
		}
		return $this;
	}
}

/**
 * Generic test class
 */
class Model_Test extends UnitTest {
	public static function data_themePaths(): array {
		$model = Model::class;
		$testModel = TestModel::class;
		return [
			[['zesk/Model/view'], $model, ''], [['/test'], $model, '/test'], [['./test'], $model, './test'],
			[['zesk/Model/test'], $model, 'test'], [['zesk/TestModel/view', 'zesk/Model/view'], $testModel, ''],
			[['/test'], $testModel, '/test'], [['./not-Filtered'], $testModel, './not-Filtered'],
			[['zesk/TestModel/test', 'zesk/Model/test'], $testModel, 'test'],
			[['zesk/TestModel/path/to/Another', 'zesk/Model/path/to/Another'], $testModel, 'path/to/Another'],
		];
	}

	/**
	 * @dataProvider data_themePaths
	 * @param array $expected
	 * @param Model $model
	 * @param array|string $theme_names
	 * @return void
	 */
	public function test_themePaths(array $expected, string $modelClass, array|string $theme_names): void {
		$model = $this->application->modelFactory($modelClass);
		$this->assertEquals($expected, $model->themePaths($theme_names));
	}

	/**
	 * @return void
	 */
	public function test_access(): void {
		$model = Model::factory($this->application, TestModel::class);

		$this->assertInstanceOf(TestModel::class, $model);
		assert($model instanceof TestModel);

		$variables = $model->variables();
		$this->assertEquals([
			'options' => [], 'application' => $this->application, 'thing' => '', 'thingTwo' => 0,
			'_class' => TestModel::class, '_parentClass' => Model::class,
		], $variables);

		$model = new TestModel($this->application);
		$model->thing = '{a} plus {b}';
		$model = $model->map(['a' => 'code', 'b' => 'logic']);
		$this->assertEquals('code plus logic', $model->thing);

		$model->set('thing', 'whatever');
		$this->assertEquals('whatever', $model->thing);

		$model['thing'] = 'another';
		$this->assertEquals('another', $model->thing);
		$model['thingTwo'] = $randomInt = $this->randomInteger();
		$this->assertEquals($randomInt, $model->thingTwo);

		$this->assertTrue(isset($model['thingTwo']));
		$this->assertFalse(isset($model['thingThree']));

		$this->assertTrue($model->hasAny('thing'));
		$this->assertTrue($model->hasAny('thingTwo'));
		$this->assertTrue($model->hasAny(['thing', 'thingTwo']));
		$this->assertFalse($model->hasAny(['thang', 'thangTwo']));
		$this->assertFalse($model->hasAny('thang'));
		$this->assertFalse($model->hasAny([null]));

		$this->assertTrue($model->hasAll('thing'));
		$this->assertFalse($model->hasAll('thing;thingTwo'));
		$this->assertTrue($model->hasAll(toList('thing;thingTwo')));
		$this->assertTrue($model->hasAll(['thing', 'thingTwo']));
		$this->assertFalse($model->hasAll(['thing', 'thingTwo', 'thingThree']));
		$this->assertFalse($model->hasAll(['thing', 'thingTwo', null]));

		$this->assertTrue(isset($model['thingTwo']));
		$model['thingTwo'] = 9421;
		$this->assertTrue(isset($model['thingTwo']));
		/* You CAN SET IT TO NULL */
		unset($model['thingTwo']);
		$this->assertNull($model['thingTwo']);
		/* Not allowed as it is typed */ /* You CAN NOT ACCESS IT BEFORE SETTING IT or checking ISSET */
		/* $this->assertEquals(9421, $model->thingTwo); fails if it is next */
		$model->thingTwo = 9421;
		$this->assertEquals(9421, $model->thingTwo);
	}

	public function test_base(): void {
		$model = Model::factory($this->application, Model::class);
		$this->assertEquals(0, $model->id());

		$model->setId(2);
		$this->assertEquals(0, $model->id());

		$dup = $model->modelFactory($model::class);
		$this->assertEquals($dup, $model);
	}

	/**
	 * @param mixed $expected
	 * @param Model $model
	 * @param mixed $source
	 * @return void
	 * @dataProvider data_applyMap
	 */
	public function test_applyMap(mixed $expected, string $modelClass, array $modelAttributes, mixed $source): void {
		$expected = $this->applyClosures($expected);
		$model = $this->application->modelFactory($modelClass, $modelAttributes);
		$source = $this->applyClosures($source);
		$this->assertEquals($expected, $model->applyMap($source));
	}

	public static function data_applyMap(): array {
		$testModel = ['thing' => 'one', 'thingTwo' => 4];
		return [
			[null, TestModel::class, $testModel, null],
			[9329, TestModel::class, $testModel, 9329],
			[['one', '4', 'zesk\\TestModel'], TestModel::class, $testModel, ['{thing}', '{thingTwo}', '{_class}']],
			[['one', '4', 'zesk\\Model'], TestModel::class, $testModel, ['{thing}', '{thingTwo}', '{_parentClass}']],
			['oneoneoneone - 4+4', TestModel::class, $testModel, '{thing}{thing}{thing}{thing} - {thingTwo}+{thingTwo}', ],
			[
				fn () => new TestModel(self::app(), ['thing' => 'one - 4', 'thingTwo' => 99]), TestModel::class, $testModel, fn () => new TestModel(self::app(), ['thing' => '{thing} - {thingTwo}', 'thingTwo' => 99]),
			],
		];
	}
}