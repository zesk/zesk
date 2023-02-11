<?php
declare(strict_types=1);

namespace zesk;

class UserAgentTest extends UnitTest {
	public static function data_userAgents(): array {
		return [
			// Samples @todo Move to test
			//
			// 2012-11-08
			//
			[
				['chrome' => true, 'mac' => true, 'macIntel' => true, 'desktop' => true, 'webkit' => true], [
					'platform' => 'mac', 'browser' => 'chrome', 'mobile' => 'desktop',
				], 'Chrome on MacOS X',
				'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_5) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.11',
			], [
				['safari' => true, 'mac' => true, 'macIntel' => true, 'desktop' => true, 'webkit' => true], [
					'platform' => 'mac', 'browser' => 'safari', 'mobile' => 'desktop',
				], 'Safari on MacOS X',
				'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_5) AppleWebKit/536.26.17 (KHTML, like Gecko) Version/6.0.2 Safari/536.26.17',
			], [
				['firefox' => true, 'mac' => true, 'macIntel' => true, 'desktop' => true], [
					'platform' => 'mac', 'browser' => 'firefox', 'mobile' => 'desktop',
				], 'Firefox on MacOS X',
				'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:16.0) Gecko/20100101 Firefox/16.0',
			], [
				['firefox' => true, 'windows' => true, 'desktop' => true], [
					'platform' => 'windows', 'browser' => 'firefox', 'mobile' => 'desktop',
				], 'Firefox on Windows XP',
				'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.1.8) Gecko/20100202Firefox/3.5.8 (.NET CLR 3.5.30729)',
			], [
				['webkit' => true, 'safari' => true, 'windows' => true, 'desktop' => true], [
					'platform' => 'windows', 'browser' => 'safari', 'mobile' => 'desktop',
				], 'Safari on Windows XP',
				'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/534.57.2 (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2',
			], [
				['opera' => true, 'mac' => true, 'macIntel' => true, 'desktop' => true], [
					'platform' => 'mac', 'browser' => 'unknown', 'mobile' => 'desktop',
				], 'Opera on Mac OS X',
				'Opera/9.80 (Macintosh; Intel Mac OS X 10.7.5; U; en) Presto/2.10.289 Version/12.02',
			],
		];
	}

	public static array $sample = [
		'platform' => 'mac', 'browser' => 'unknown', 'mobile' => 'desktop',
	];

	/**
	 * @param array $attributes
	 * @param array $classify
	 * @param string $description
	 * @param string $userAgent
	 * @return void
	 * @dataProvider data_userAgents
	 */
	public function test_userAgents(array $attributes, array $classify, string $description, string $userAgent): void {
		$ua = new UserAgent($userAgent);
		$attributes['userAgent'] = $userAgent;
		$attributes['lowUserAgent'] = strtolower($userAgent);
		$this->assertEquals($attributes, array_filter($ua->attributes()), "$description $userAgent failed attributes");
		$this->assertEquals($classify, array_filter($ua->classify()), "$description $userAgent failed classify");
	}
}
