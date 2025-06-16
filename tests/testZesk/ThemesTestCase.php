<?php declare(strict_types=1);

namespace testZesk;

use zesk\Exception\Redirect;

/**
 * @coversDefaultClass \zesk\Themes
 */
class ThemesTestCase extends TestCase
{
	/**
	 * @covers ::theme
	 *
	 * @throws Redirect
	 */
	public function test_theme(): void
	{
		$themes = $this->application->themes;

		$this->assertEquals('42.5123', $themes->theme('microsecond', [42.512312]));
		$this->assertEquals('42.5%', $themes->theme('percent', [
			42.512312,
			1,
		]));
		$this->assertEquals('42.6%', $themes->theme('percent', [
			42.552312,
			1,
		]));
		$this->assertEquals('42.6%', $themes->theme('percent', [
			42.552312,
			1,
		]));
		$this->assertEquals('43%', $themes->theme('percent', [
			42.552312,
			0,
		]));
		$this->assertEquals('43%', $themes->theme('percent', [
			42.552312,
		]));

		$this->assertEquals('1 KB', $themes->theme('bytes', [
			1024,
		]));
	}
}
