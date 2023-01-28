<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @copyright &copy; 2023 Market Acumen, Inc.
 * @author kent
 */
namespace zesk;

use \stdClass;

/**
 * Generic test class
 */
class MIME_Test extends UnitTest {
	public function data_from_bad(): array {
		return [
			['who/wants/a/foo.iges1', ],
			['who/wants/a/foo.dessr', ],
			['who/wants/a/foo.ssnbp', ],
		];
	}

	/**
	 * @param string $expected
	 * @param string $tested
	 * @return void
	 * @throws Exception_Key
	 * @dataProvider data_from_bad
	 */
	public function test_from_bad(string $tested): void {
		$this->expectException(Exception_Key::class);
		MIME::fromExtension($tested);
	}

	public function data_from(): array {
		return [
			['model/iges', 'who/wants/a/foo.iges', ],
			['application/x-x509-ca-cert', 'who/wants/a/foo.crt', ],
			['application/vnd.wolfram.player', 'who/wants/a/foo.nbp', ],
		];
	}

	/**
	 * @param string $expected
	 * @param string $tested
	 * @return void
	 * @throws Exception_Key
	 * @dataProvider data_from
	 */
	public function test_from(string $expected, string $tested): void {
		$this->assertEquals($expected, MIME::fromExtension($tested));
	}

	public function data_to(): array {
		return [
			['igs', 'model/iges'],
			['der', 'application/x-x509-ca-cert'],
			['nbp', 'application/vnd.wolfram.player', ],
		];
	}

	/**
	 * @param string $expected
	 * @param string $tested
	 * @return void
	 * @throws Exception_Key
	 * @dataProvider data_to
	 */
	public function test_to(string $expected, string $tested): void {
		$this->assertEquals($expected, MIME::toExtension($tested));
	}

	public function data_to_bad(): array {
		return [
			['model//iges'],
			['application/y-x509-ca-cert'],
			['application/vnd.wolfram', ],
		];
	}

	/**
	 * @param string $tested
	 * @return void
	 * @throws Exception_Key
	 * @dataProvider data_to_bad
	 */
	public function test_to_bad(string $tested): void {
		$this->expectException(Exception_Key::class);
		MIME::toExtension($tested);
	}
}
