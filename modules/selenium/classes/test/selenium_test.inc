<?php
class Selenium_Test extends Test_Unit {
	function test_selenium_basics() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$test_selenium = Module_Selenium::is_enabled();
		if (!$test_selenium) {
			$this->log("Skipping Selenium test due to Module_Selenium::is_enabled() value ...");
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