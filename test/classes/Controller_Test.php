<?php
class Controller_Tests extends Test_Unit {
	function test_factory() {
		$class = "Controller";
		$app = $this->application;
		$options = null;
		Controller::factory($class, $app, $options);
	}
	function test_before_after() {
		$app = $this->application;
		$options = null;
		$testx = new Controller($app, $options);

		$testx->before();

		$testx->after();
	}
}