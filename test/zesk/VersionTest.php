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
class VersionTest extends UnitTest
{
	/**
	 * @return void
	 * @see Version
	 */
	public function test_everything(): void
	{
		$release = Version::release();
		$this->assertStringStartsNotWith('-', $release);
		$date = Version::date();
		$this->assertStringStartsNotWith('-', $date);
		$this->assertTrue(Types::isDate($date), "is_date($date)");
		$this->assertLessThan(12.0, Timestamp::now()->difference(Timestamp::factory()->parse($date), Temporal::UNIT_MONTH), "Released within 12 months ($date)");

		$string = Version::string($this->application->locale);
		$this->assertStringContainsString($date, $string);
		$this->assertStringContainsString($release, $string);

		$vars = Version::variables();
		$keys = ['release', 'date'];
		$this->assertArrayHasKeys($keys, $vars);
		foreach ($keys as $key) {
			$this->assertIsString($vars[$key], "\$vars[$key] is not a string");
		}
	}
}
