<?php
declare(strict_types=1);
/**
 * @package zeskTest
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 * @subpackage Classes
 */

namespace testZesk\Application;

use testZesk\TestCase;
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
		$this->assertEquals($app->classes->hierarchy(__NAMESPACE__ . '\\AClass'), [
			"${myPrefix}AClass",
			"${zPrefix}Hookable",
			"${zPrefix}Options",
		]);
		$this->assertEquals($app->classes->hierarchy(__NAMESPACE__ . '\\BClass'), [
			"${myPrefix}BClass",
			"${myPrefix}AClass",
			"${zPrefix}Hookable",
			"${zPrefix}Options",
		]);
		$this->assertEquals($app->classes->hierarchy(__NAMESPACE__ . '\\CClass'), [
			"${myPrefix}CClass",
			"${myPrefix}BClass",
			"${myPrefix}AClass",
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
