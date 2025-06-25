<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @copyright &copy; 2025, Market Acumen, Inc.
 * @author     kent
 */

namespace testZesk;

use zesk\Exception\ClassNotFound;
use zesk\Model;

/**
 * Generic test class
 * @coversDefaultClass  \zesk\Model
 * @uses   Model
 */
class ModelTest extends TestCase
{
	public static function data_themePaths(): array
	{
		$model = TestModel::class;
		$testModel = TestModelChild::class;
		return [
			[
				[
					'testZesk/TestModel/view',
					'zesk/Model/view',
				],
				$model,
				'',
			],
			[
				['/test'],
				$model,
				'/test',
			],
			[
				['./test'],
				$model,
				'./test',
			],
			[
				[
					'testZesk/TestModel/test',
					'zesk/Model/test',
				],
				$model,
				'test',
			],
			[
				[
					'testZesk/TestModelChild/view',
					'testZesk/TestModel/view',
					'zesk/Model/view',
				],
				$testModel,
				'',
			],
			[
				['/test'],
				$testModel,
				'/test',
			],
			[
				['./not-Filtered'],
				$testModel,
				'./not-Filtered',
			],
			[
				[
					'testZesk/TestModelChild/test',
					'testZesk/TestModel/test',
					'zesk/Model/test',
				],
				$testModel,
				'test',
			],
			[
				[
					'testZesk/TestModelChild/path/to/Another',
					'testZesk/TestModel/path/to/Another',
					'zesk/Model/path/to/Another',
				],
				$testModel,
				'path/to/Another',
			],
		];
	}

	/**
	 * @param array $expected
	 * @param string $modelClass
	 * @param array|string $theme_names
	 * @return void
	 * @dataProvider data_themePaths
	 * @covers ::themePaths
	 * @throws ClassNotFound
	 */
	public function test_themePaths(array $expected, string $modelClass, array|string $theme_names): void
	{
		$model = $this->application->modelFactory($modelClass);
		$this->assertEquals($expected, $model->themePaths($theme_names));
	}

	/**
	 * @return void
	 * @coversNothing
	 */
	public function test_access(): void
	{
		$model = new TestModel($this->application);

		$this->assertInstanceOf(TestModel::class, $model);

		$model->thing = 'another';
		$model->thingTwo = $randomInt = $this->randomInteger();
		$this->assertEquals($randomInt, $model->thingTwo);
		$model->thingTwo = 9421;
		$this->assertEquals(9421, $model->thingTwo);
	}

	/**
	 * @return void
	 * @coversNothing
	 */
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
