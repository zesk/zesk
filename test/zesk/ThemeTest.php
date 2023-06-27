<?php
declare(strict_types=1);

namespace zesk;

class ThemeTest extends UnitTest
{
	public function test_themePath(): void
	{
		$prefix = '';
		$result = $this->application->addThemePath(__DIR__, $prefix);
		$this->assertInstanceOf(Application::class, $result);
		$paths = $this->application->themes->themePath();
		$this->assertTrue(in_array(__DIR__, $paths[$prefix]));
	}

	/**
	 */
	public function test_theme(): void
	{
		$type = 'dude';
		$this->application->themes->theme($type, [
			'hello' => 1,
		]);
	}
}
