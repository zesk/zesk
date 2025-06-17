<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @copyright  &copy; 2025, Market Acumen, Inc.
 * @author     kent
 */

namespace testZesk;

use zesk\Exception\ParseException;

use zesk\Version;
use zesk\Types;
use zesk\Timestamp;
use zesk\Temporal;

/**
 * Generic test class
 *
 * @uses Version
 * @coversDefaultClass \zesk\Version
 */
class VersionTest extends TestCase
{
	/**
	 * @return void
	 * @throws ParseException
	 * @see Version
	 * @covers ::release ::date ::string ::variables
	 */
	public function test_everything(): void
	{
		$release = Version::release();
		$this->assertStringStartsNotWith('-', $release);
		$date = Version::date();
		$this->assertStringStartsNotWith('-', $date);
		$this->assertTrue(Types::isDate($date), "is_date($date)");
		$this->assertLessThan(25.0, Timestamp::now()->difference(Timestamp::factory()->parse($date), Temporal::UNIT_MONTH), "Released within 25 months ($date)");

		$string = Version::string($this->application->locale);
		$this->assertStringContainsString($date, $string);
		$this->assertStringContainsString($release, $string);

		$vars = Version::variables();
		$keys = [
			'release',
			'date',
		];
		$this->assertArrayHasKeys($keys, $vars);
		foreach ($keys as $key) {
			$this->assertIsString($vars[$key], "\$vars[$key] is not a string");
		}
	}
}
