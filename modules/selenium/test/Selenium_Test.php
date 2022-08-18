<?php declare(strict_types=1);
namespace zesk;

class Selenium_Test extends UnitTest {
	protected array $load_modules = [
		'Selenium',
	];

	public function test_selenium_basics(): void {
		$options = [
			'browser' => '*chrome',
			'url' => 'http://www.addresslock.com',
			'host' => $host = $this->option('selenium_host', null),
		];
		if (!$host) {
			$this->log('Selenium is not enabled, set {class}::selenium_host to valid IP or Domain Name', [
				'class' => get_class($this),
			]);
			return;
		}
		$selenium = new Test_Selenium_Legacy($this->application, $options);
		$selenium->start();

		$selenium->open('/');
		$selenium->waitForPageToLoad('30000');

		$selenium->type('email', 'kent@marketruler.com');
		$selenium->type('href', 'Text');
		$selenium->click('link=MarketRuler');
		$selenium->waitForPageToLoad('30000');

		$this->assert(str_contains($selenium->getTitle(), 'Marketing Power Tools'));

		$selenium->stop();
	}
}
