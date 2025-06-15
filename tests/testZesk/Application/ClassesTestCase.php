<?php
declare(strict_types=1);

namespace testZesk\Application;

use testZesk\TestCase;
use zesk\Hookable;
use zesk\HTML;

/**
 * @coversDefaultClass \zesk\Configuration
 */
class ClassesTestCase extends TestCase
{
	/**
	 * @return void
	 * @covers \zesk\Application\Classes::hierarchy
	 */
	public function test_class_hierarchy(): void
	{
		$app = $this->application;

		$myPrefix = __NAMESPACE__ . '\\';
		$zPrefix = 'zesk\\';
		$this->assertEquals($app->classes->hierarchy(__NAMESPACE__ . '\\A'), [
			"${myPrefix}A",
			"${zPrefix}Hookable",
			"${zPrefix}Options",
		]);
		$this->assertEquals($app->classes->hierarchy(__NAMESPACE__ . '\\B'), [
			"${myPrefix}B",
			"${myPrefix}A",
			"${zPrefix}Hookable",
			"${zPrefix}Options",
		]);
		$this->assertEquals($app->classes->hierarchy(__NAMESPACE__ . '\\C'), [
			"${myPrefix}C",
			"${myPrefix}B",
			"${myPrefix}A",
			"${zPrefix}Hookable",
			"${zPrefix}Options",
		]);
		$this->assertEquals($app->classes->hierarchy(HTML::class), [$zPrefix . 'HTML']);
		$this->assertEquals($app->classes->hierarchy(new A($this->application)), [
			$myPrefix . 'A',
			$zPrefix . 'Hookable',
			$zPrefix . 'Options',
		]);
	}
}

class A extends Hookable
{
}

/**
 *
 * @author kent
 *
 */
class B extends A
{
}

/**
 *
 * @author kent
 *
 */
class C extends B
{
}
