<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @copyright &copy; 2023 Market Acumen, Inc.
 * @author kent
 */

namespace zesk;

/**
 * Generic test class
 */
class Model_Test extends UnitTest
{
	public static function data_themePaths(): array
	{
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
	 * @param array $expected
	 * @param string $modelClass
	 * @param array|string $theme_names
	 * @return void
	 * @throws Exception\ClassNotFound
	 * @dataProvider data_themePaths
	 */
	public function test_themePaths(array $expected, string $modelClass, array|string $theme_names): void
	{
		$model = $this->application->modelFactory($modelClass);
		$this->assertEquals($expected, $model->themePaths($theme_names));
	}

	/**
	 * @return void
	 */
	public function test_access(): void
	{
		$model = new TestModel($this->application);

		$this->assertInstanceOf(TestModel::class, $model);

		$model->thing = 'another';
		$this->assertEquals('another', $model->thing);
		$model->thingTwo = $randomInt = $this->randomInteger();
		$this->assertEquals($randomInt, $model->thingTwo);
		$model->thingTwo = 9421;
		$this->assertEquals(9421, $model->thingTwo);
	}

	public function test_base(): void
	{
		$model = new TestModel($this->application);

		$model->thingTwo = 3;
		$this->assertEquals(3, $model->thingTwo);

		$model->setId(2);
		$this->assertEquals(2, $model->thingTwo);

		$model->setId('33');
		$this->assertEquals(2, $model->thingTwo);
	}
}
