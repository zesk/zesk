<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @copyright &copy; 2025, Market Acumen, Inc.
 * @author     kent
 */

namespace testZesk;

use zesk\Exception\KeyNotFound;
use zesk\MIME;

/**
 * @coversDefaultClass \zesk\MIME
 */
class MIMETest extends TestCase
{
	public static function data_from_bad(): array
	{
		return [
			['who/wants/a/foo.iges1', ],
			['who/wants/a/foo.dessr', ],
			['who/wants/a/foo.ssnbp', ],
		];
	}

	/**
	 * @covers ::fromExtension
	 * @param string $tested
	 * @return void
	 * @throws KeyNotFound
	 * @dataProvider data_from_bad
	 */
	public function test_from_bad(string $tested): void
	{
		$this->expectException(KeyNotFound::class);
		MIME::fromExtension($tested);
	}

	public static function data_from(): array
	{
		return [
			[
				'model/iges',
				'who/wants/a/foo.iges',
			],
			[
				'application/x-x509-ca-cert',
				'who/wants/a/foo.crt',
			],
			[
				'application/vnd.wolfram.player',
				'who/wants/a/foo.nbp',
			],
		];
	}

	/**
	 * @covers ::fromExtension
	 * @param string $expected
	 * @param string $tested
	 * @return void
	 * @throws KeyNotFound
	 * @dataProvider data_from
	 */
	public function test_from(string $expected, string $tested): void
	{
		$this->assertEquals($expected, MIME::fromExtension($tested));
	}

	public static function data_to(): array
	{
		return [
			[
				'igs',
				'model/iges',
			],
			[
				'der',
				'application/x-x509-ca-cert',
			],
			[
				'nbp',
				'application/vnd.wolfram.player',
			],
		];
	}

	/**
	 * @covers ::toExtension
	 * @param string $expected
	 * @param string $tested
	 * @return void
	 * @throws KeyNotFound
	 * @dataProvider data_to
	 */
	public function test_to(string $expected, string $tested): void
	{
		$this->assertEquals($expected, MIME::toExtension($tested));
	}

	public static function data_to_bad(): array
	{
		return [
			['model//iges'],
			['application/y-x509-ca-cert'],
			['application/vnd.wolfram', ],
		];
	}

	/**
	 * @param string $tested
	 * @return void
	 * @throws KeyNotFound
	 * @dataProvider data_to_bad
	 * @covers ::toExtension
	 */
	public function test_to_bad(string $tested): void
	{
		$this->expectException(KeyNotFound::class);
		MIME::toExtension($tested);
	}
}
