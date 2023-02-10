<?php
declare(strict_types=1);

namespace zesk\ORM;

use zesk\UnitTest;

/**
 *
 * @author kent
 *
 */
class Domain_Test extends UnitTest {
	protected array $load_modules = [
		'MySQL',
		'ORM',
	];

	/**
	 *
	 */
	public static function cookie_domain_data(): array {
		return [
			[
				'conversion.kent.glucose',
				'kent.glucose',
			],
			[
				'www.conversionruler.com',
				'conversionruler.com',
			],
			[
				'hello.www.conversionruler.com',
				'conversionruler.com',
			],
			[
				'test.conversionruler.com',
				'conversionruler.com',
			],
			[
				'another-fucking-thing.roi-tracking.com',
				'roi-tracking.com',
			],
			[
				'Hello',
				'hello',
			],
			[
				'joe.com',
				'joe.com',
			],
		];
	}

	/**
	 * @dataProvider cookie_domain_data
	 * @param string $domain
	 * @param string $expected
	 */
	public function test_cookie_domains(string $domain, string $expected): void {
		$cookie_domain = Domain::domainFactory($this->application, $domain)->computeCookieDomain();
		$this->assertEquals($cookie_domain, $expected, "$domain cookie domain => $cookie_domain !== $expected");
	}
}
