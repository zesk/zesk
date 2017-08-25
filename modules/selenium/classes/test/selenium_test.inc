<?php
namespace zesk;

class Selenium_Test extends Test_Unit {
	protected $load_modules = array(
		"Selenium"
	);
	function test_selenium_basics() {
		$options = array(
			"browser" => "*chrome",
			"url" => "http://www.addresslock.com",
			"host" => $host = $this->option("selenium_host", null)
		);
		if (!$host) {
			$this->log("Selenium is not enabled, set {class}::selenium_host to valid IP or Domain Name", array(
				"class" => get_class($this)
			));
			return;
		}
		$selenium = new Test_Selenium_Legacy($this->application, $options);
		$selenium->start();
		
		$selenium->open("/");
		$selenium->waitForPageToLoad("30000");
		
		$selenium->type("email", "kent@marketruler.com");
		$selenium->type("href", "Text");
		$selenium->click("link=MarketRuler");
		$selenium->waitForPageToLoad("30000");
		
		$this->assert(strpos($selenium->getTitle(), "Marketing Power Tools") !== false);
		
		$selenium->stop();
	}
}
