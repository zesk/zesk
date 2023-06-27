<?php
declare(strict_types=1);

namespace zesk;

use zesk\Interface\SettingsInterface;
use zesk\PHPUnit\TestCase;

class SettingsTestCase extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		// pass
	}

	public function assertSettings(array $expected, SettingsInterface $settings): void
	{
	}
}
