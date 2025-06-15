<?php declare(strict_types=1);

namespace testZesk;

use zesk\Exception\ClassNotFound;
use zesk\Model;

/**
 * @coversDefaultClass \zesk\Application\Objects
 */
class ObjectsTestCase extends TestCase
{
	/**
	 * @return void
	 * @throws ClassNotFound
	 * @covers ::factory ::factoryArguments
	 */
	public function test_factory(): void
	{
		$class = 'zesk\\Model';
		$init = [
			'a' => 123,
		];
		$object = $this->application->objects->factory($class, $this->application, $init);
		/* @var $object Model */
		$this->assertInstanceOf(Model::class, $object);

		// 2023 Model no longer has initializer
		$this->assertEquals(['application'], array_keys(get_object_vars($object)));
	}
}
