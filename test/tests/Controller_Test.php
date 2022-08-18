<?php declare(strict_types=1);
namespace zesk;

class Controller_Test extends UnitTest {
	/**
	 *
	 */
	public function test_before_after(): void {
		$app = $this->application;
		$options = [];
		$route = null;
		$response = null;
		$testx = new Controller($app, $route, $response, $options);

		$testx->before();

		$testx->after();
	}
}
