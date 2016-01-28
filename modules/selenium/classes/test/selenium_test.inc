<?php
class Selenium_Test extends Test_Unit {

	function test_selenium_basics() {
		if (!zesk::getb('TEST_SELENIUM')) {
			$this->log("Skipping Selenium test due to TEST_SELENIUM value ...");
			return true;
		}
		$selenium = new Test_Selenium("*chrome", "http://www.addresslock.com", "192.168.0.201");
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