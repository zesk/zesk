<?php
class Growl_Test extends Test_Unit {

	function initialize() {
		parent::initialize();
		Module::load('growl');
		$this->assert_module('growl');
	}
	function test_growl() {
		$growl_url = $this->option("url");

		$growl = Growl::factory($growl_url, array(
			"name" => "Zesk Test",
			"hash_algorithm" => "sha256",
		));
		$growl->notification("Test");
		$growl->register();

		$growl->notify("Test", "Title", "Message");
	}

	function no_test_growl_original() {
		require_once ZESK_ROOT . 'modules/growl/classes/class.growl.inc';

		// Setup
		$growl = new Growl();
		$growl->setAddress('127.0.0.1');
		$growl->addNotification("Test");
		$growl->register();

		// Send Notification
		$growl->notify("Test", "Test Alert", "The body of the test alert!");
	}
}