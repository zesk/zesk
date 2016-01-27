<?php
class Controller_Tests extends Test_Unit {

	function test_factory() {
		$class = "Controller";
		$app = Application::instance();
		$options = null;
		Controller::factory($class, $app, $options);
	}

	function test_before_after() {
		$app = Application::instance();
		$options = null;
		$testx = new Controller($app, $options);

		$testx->before();

		$testx->after();
	}
}
