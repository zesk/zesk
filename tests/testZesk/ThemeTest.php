<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Application;
use zesk\Exception\DirectoryNotFound;

/**
 * @uses Theme
 */
class ThemeTest extends TestCase
{
	/**
	 * @return void
	 * @throws DirectoryNotFound
	 * @covers \zesk\Application::addThemePath
	 */
	public function test_themePath(): void
	{
		$prefix = '';
		$result = $this->application->addThemePath(__DIR__, $prefix);
		$this->assertInstanceOf(Application::class, $result);
		$paths = $this->application->themes->themePath();
		$this->assertTrue(in_array(__DIR__, $paths[$prefix]));
	}

	/**
	 * @covers \zesk\Themes::theme
	 */
	public function test_theme(): void
	{
		$type = 'dude';
		$this->application->themes->theme($type, [
			'hello' => 1,
		]);
	}
}
